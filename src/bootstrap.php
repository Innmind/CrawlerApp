<?php
declare(strict_types = 1);

namespace Crawler;

use function Innmind\Html\bootstrap as html;
use function Innmind\HttpTransport\bootstrap as transport;
use function Innmind\Crawler\bootstrap as crawler;
use function Innmind\Rest\Client\bootstrap as rest;
use function Innmind\Homeostasis\bootstrap as homeostasis;
use function Innmind\AMQP\bootstrap as amqp;
use function Innmind\Logger\bootstrap as logger;
use function Innmind\Stack\stack;
use function Innmind\IPC\bootstrap as ipc;
use Innmind\CLI\Framework\Application;
use Innmind\Url\{
    Url,
    Path,
};
use Innmind\TimeContinuum\Earth\ElapsedPeriod;
use Innmind\UrlResolver\UrlResolver;
use Innmind\Homeostasis\Factor;
use Innmind\LogReader\{
    Reader\Synchronous,
    Reader\LineParser\Monolog,
};
use Innmind\RobotsTxt\{
    Parser\Parser,
    Parser\Walker,
};
use Innmind\Socket\Internet\Transport as Socket;
use Innmind\AMQP\{
    Model\Exchange,
    Model\Queue,
    Client,
};
use Innmind\TimeWarp\Halt\Usleep;
use Innmind\IPC\Process\Name;
use Innmind\Xml;
use Innmind\Immutable\{
    Set,
    Map,
    Str,
};
use Psr\Log\LogLevel;

function bootstrap(Application $app): Application
{
    /** @psalm-suppress all */
    return $app
        ->service('rootDir', static fn() => Path::of(__DIR__.'/../'))
        ->service(
            'appLogUrl',
            static fn($env, $os, $service) => Url::of("file://{$service('rootDir')->toString()}var/logs/app.log"),
        )
        ->service(
            'amqpLogUrl',
            static fn($env, $os, $service) => Url::of("file://{$service('rootDir')->toString()}var/logs/amqp/amqp.log"),
        )
        ->service(
            'restCache',
            static fn($env, $os, $service) => $os->filesystem()->mount(
                $service('rootDir')->resolve(Path::of('var/cache/rest/')),
            ),
        )
        ->service(
            'logs',
            static fn($env, $os, $service) => $os->filesystem()->mount(
                $service('rootDir')->resolve(Path::of('var/logs/')),
            ),
        )
        ->service(
            'homeostasisStates',
            static fn($env, $os, $service) => $os->filesystem()->mount(
                $service('rootDir')->resolve(Path::of('var/data/states/')),
            ),
        )
        ->service(
            'homeostasisActions',
            static fn($env, $os, $service) => $os->filesystem()->mount(
                $service('rootDir')->resolve(Path::of('var/data/actions/')),
            ),
        )
        ->service(
            'traces',
            static fn($env, $os, $service) => $os->filesystem()->mount(
                $service('rootDir')->resolve(Path::of('var/cache/traces/')),
            ),
        )
        ->service(
            'robots',
            static fn($env, $os, $service) => $os->filesystem()->mount(
                $service('rootDir')->resolve(Path::of('var/cache/robots_txts/')),
            ),
        )
        ->service('amqpTransport', static fn() => Socket::tcp())
        ->service('amqpServer', static fn($env) => Url::of($env->variables()->get('AMQP_SERVER')))
        ->service('userAgent', static fn() => Str::of('Innmind Robot'))
        ->service('logger', static fn($env, $os, $service) => logger('app', $service('appLogUrl'))(LogLevel::ERROR))
        ->service('amqpLogger', static fn($env, $os, $service) => logger('amqp', $service('amqpLogUrl'))(LogLevel::ERROR))
        ->service('log', static fn($env, $os, $service) => transport()['logger']($service('logger')))
        ->service('transport', static fn($env, $os, $service) => stack(
            transport()['throw_on_error'],
            $service('log'),
        )($os->remote()->http()))
        ->service('urlResolver', static fn() => new UrlResolver)
        ->service('rest', static fn($env, $os, $service) => rest(
            new Transport\Authentified(
                $service('transport'),
                $env->variables()->get('API_KEY'),
            ),
            $service('urlResolver'),
            $service('restCache'),
        ))
        ->service('cacheStorage', static fn() => new Xml\Reader\Cache\Storage)
        ->service('reader', static fn($env, $os, $service) => new Xml\Reader\Cache(
            html(),
            $service('cacheStorage'),
        ))
        ->service('factors', static fn($env, $os, $service) => Set::of(
            Factor::class,
            Homeostasis\Factors::cpu($os->clock(), $os->status()),
            Homeostasis\Factors::log(
                $os->clock(),
                new Synchronous(new Monolog($os->clock())),
                $service('logs'),
            ),
        ))
        ->service('actuator', static fn($env, $os, $service) => new Homeostasis\Actuator\ProvisionConsumers(
            $os->status(),
            $os->control(),
            $service('logger'),
            $env->workingDirectory()->toString(),
        ))
        ->service('regulator', static function($env, $os, $service) {
            $homeostasis = homeostasis(
                $service('factors'),
                $service('actuator'),
                $service('homeostasisStates'),
                $os->clock(),
            );

            return $homeostasis['modulate_state_history']($service('homeostasisActions'))(
                $homeostasis['regulator'],
            );
        })
        ->service('basicAmqpClient', static fn($env, $os, $service) => amqp()['client']['basic'](
            $service('amqpTransport'),
            $service('amqpServer'),
            new ElapsedPeriod(60000), // one minute
            $os->clock(),
            $os->process(),
            $os->remote(),
            $os->sockets(),
            $service('amqpLogger'),
        ))
        ->service('exchanges', static fn() => Set::of(
            Exchange\Declaration::class,
            Exchange\Declaration::durable('urls', Exchange\Type::direct()),
        ))
        ->service('queues', static fn() => Set::of(
            Queue\Declaration::class,
            Queue\Declaration::durable()->withName('crawler'),
        ))
        ->service('bindings', static fn() => Set::of(
            Queue\Binding::class,
            new Queue\Binding('urls', 'crawler'),
        ))
        ->service('amqpClient', static fn($env, $os, $service) => stack(
            amqp()['client']['auto_declare'](
                $service('exchanges'),
                $service('queues'),
                $service('bindings'),
            ),
            static fn(Client $client): Client => amqp()['client']['signal_aware'](
                $client,
                $os->process()->signals(),
            ),
            static fn(Client $client): Client => amqp()['client']['logger'](
                $client,
                $service('amqpLogger'),
            ),
            amqp()['client']['fluent'],
        )($service('basicAmqpClient')))
        ->service(
            'producer',
            static fn($env, $os, $service) => amqp()['producers']($service('exchanges'))($service('amqpClient'))->get('urls'),
        )
        ->service('tracer', static fn($env, $os, $service) => new CrawlTracer\CrawlTracer(
            $service('traces'),
            $os->clock(),
        ))
        ->service('robotsParser', static fn($env, $os, $service) => new RobotsTxt\KeepInMemoryParser(
            new RobotsTxt\CacheParser(
                new Parser(
                    $service('log')($os->remote()->http()),
                    $service('userAgent')->toString(),
                ),
                new Walker,
                $service('robots'),
            ),
        ))
        ->service('delayer', static fn($env, $os, $service) => new Delayer\ThresholdDelayer(
            new Delayer\RobotsTxtAwareDelayer(
                $service('robotsParser'),
                $service('userAgent')->toString(),
                new Usleep,
                $os->clock(),
            ),
            new Delayer\TracerAwareDelayer(
                $service('tracer'),
                new Delayer\FixDelayer(
                    $os->process(),
                ),
                $os->clock(),
            ),
            $os->clock(),
        ))
        ->service('crawler', static fn($env, $os, $service) => new Crawler\XmlReaderAwareCrawler(
            $service('cacheStorage'),
            new Crawler\TracerAwareCrawler(
                $service('tracer'),
                new Crawler\RobotsAwareCrawler(
                    $service('robotsParser'),
                    new Crawler\DelayerAwareCrawler(
                        $service('delayer'),
                        crawler(
                            new Transport\MemorySafe($service('transport')),
                            $os->clock(),
                            $service('reader'),
                            $service('urlResolver'),
                        ),
                    ),
                    $service('userAgent')->toString(),
                ),
            ),
        ))
        ->service('publisher', static fn($env, $os, $service) => new Publisher\LinksAwarePublisher(
            new Publisher\ImagesAwarePublisher(
                new Publisher\AlternatesAwarePublisher(
                    new Publisher\CanonicalAwarePublisher(
                        new Publisher\Publisher(
                            $service('rest'),
                            new Translator\HttpResourceTranslator(
                                new Translator\Property\DelegationTranslator(
                                    Map::of('string', Translator\PropertyTranslator::class)
                                        ->put('host', new Translator\Property\HostTranslator)
                                        ->put('path', new Translator\Property\PathTranslator)
                                        ->put('query', new Translator\Property\QueryTranslator)
                                        ->put('languages', new Translator\Property\LanguagesTranslator)
                                        ->put('charset', new Translator\Property\CharsetTranslator)
                                        ->put('dimension', new Translator\Property\Image\DimensionTranslator)
                                        ->put('weight', new Translator\Property\Image\WeightTranslator)
                                        ->put('anchors', new Translator\Property\HtmlPage\AnchorsTranslator)
                                        ->put('android', new Translator\Property\HtmlPage\AndroidAppLinkTranslator)
                                        ->put('description', new Translator\Property\HtmlPage\DescriptionTranslator)
                                        ->put('ios', new Translator\Property\HtmlPage\IosAppLinkTranslator)
                                        ->put('journal', new Translator\Property\HtmlPage\IsJournalTranslator)
                                        ->put('mainContent', new Translator\Property\HtmlPage\MainContentTranslator)
                                        ->put('themeColour', new Translator\Property\HtmlPage\ThemeColourTranslator)
                                        ->put('title', new Translator\Property\HtmlPage\TitleTranslator)
                                        ->put('preview', new Translator\Property\HtmlPage\PreviewTranslator)
                                        ->put('author', new Translator\Property\HtmlPage\AuthorTranslator)
                                        ->put('citations', new Translator\Property\HtmlPage\CitationsTranslator)
                                ),
                            ),
                        ),
                        $service('producer'),
                    ),
                    $service('producer'),
                ),
                $service('producer'),
            ),
            $service('producer'),
        ))
        ->service('linker', static fn($env, $os, $service) => new Linker\ReferrerLinker(
            new Linker\Linker($service('rest')),
        ))
        ->service(
            'consumers',
            static fn($env, $os, $service) => Map::of('string', 'callable')
                ('crawler', new AMQP\Consumer\CrawlConsumer(
                    $service('crawler'),
                    $service('publisher'),
                    $service('linker'),
                    $service('userAgent')->toString()
                )),
        )
        ->service('ipc', static fn($env, $os, $service) => ipc($os))
        ->service('ipcChannel', static fn() => new Name('crawler-homeostasis'))
        ->service('consume_command', static fn($env, $os, $service) => new Command\Consume(
            amqp()['command']['consume']($service('consumers'))($service('amqpClient')),
            new Homeostasis\Regulator\Regulate(
                $service('ipc'),
                $service('ipcChannel'),
            ),
        ))
        ->service('crawl_command', static fn($env, $os, $service) => new Command\Crawl(
            $service('crawler'),
            $service('userAgent')->toString(),
            $service('publisher'),
        ))
        ->service('homeostasis_command', static fn($env, $os, $service) => new Command\Homeostasis(
            $service('ipc')->listen($service('ipcChannel')),
            $service('regulator'),
            $os->control()->processes(),
        ))
        ->command('consume_command')
        ->command('crawl_command')
        ->command('homeostasis_command');
}
