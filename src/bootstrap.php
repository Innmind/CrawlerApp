<?php
declare(strict_types = 1);

namespace Crawler;

use function Innmind\Html\bootstrap as html;
use function Innmind\Xml\bootstrap as xml;
use function Innmind\HttpTransport\bootstrap as transport;
use function Innmind\Crawler\bootstrap as crawler;
use function Innmind\Rest\Client\bootstrap as rest;
use function Innmind\Homeostasis\bootstrap as homeostasis;
use function Innmind\AMQP\bootstrap as amqp;
use function Innmind\Logger\bootstrap as logger;
use function Innmind\InstallationMonitor\bootstrap as monitor;
use Innmind\OperatingSystem\{
    CurrentProcess,
    Remote,
};
use Innmind\Url\{
    UrlInterface,
    PathInterface,
};
use Innmind\CLI\Commands;
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    ElapsedPeriod,
    Period\Earth\Second,
};
use Innmind\UrlResolver\UrlResolver;
use Innmind\Filesystem\Adapter;
use Innmind\Homeostasis\{
    Factor,
};
use Innmind\Server\Status\ServerFactory as ServerStatusFactory;
use Innmind\Server\Control\ServerFactory as ServerControlFactory;
use Innmind\LogReader\{
    Reader\Synchronous,
    Reader\LineParser\Symfony,
};
use Innmind\RobotsTxt\{
    Parser\Parser,
    Parser\Walker,
};
use Innmind\Socket\Internet\Transport as Socket;
use Innmind\AMQP\{
    Model\Exchange,
    Model\Queue,
};
use Innmind\TimeWarp\Halt\Usleep;
use Innmind\Immutable\{
    Set,
    Map,
};
use Psr\Log\LogLevel;

function bootstrap(
    TimeContinuumInterface $clock,
    CurrentProcess $process,
    Remote $remote,
    UrlInterface $appLog,
    UrlInterface $amqpLog,
    Adapter $restCache,
    Adapter $logs,
    Adapter $homeostasisStates,
    Adapter $homeostasisActions,
    Adapter $traces,
    Adapter $robots,
    PathInterface $workingDirectory,
    Socket $amqpTransport,
    UrlInterface $amqpServer,
    string $apiKey,
    string $userAgent
): Commands {
    $logger = logger('app', $appLog)(LogLevel::ERROR);
    $transport = transport();
    $log = $transport['logger']($logger);
    $transport = $transport['throw_on_error'](
        $log(
            $transport['default']()
        )
    );

    $xml = xml();
    $reader = $xml['cache'](html());
    $cacheStorage = $xml['cache_storage'];
    $urlResolver = new UrlResolver;
    $serverStatus = ServerStatusFactory::build($clock);

    $rest = rest(
        new Transport\Authentified(
            $transport,
            $apiKey
        ),
        $urlResolver,
        $restCache
    );

    $factors = Set::of(
        Factor::class,
        Homeostasis\Factors::cpu($clock, $serverStatus),
        Homeostasis\Factors::log(
            $clock,
            new Synchronous(new Symfony($clock)),
            $logs
        )
    );
    $actuator = new Homeostasis\Actuator\ProvisionConsumers(
        $serverStatus,
        ServerControlFactory::build(),
        $logger,
        (string) $workingDirectory
    );

    $homeostasis = homeostasis($factors, $actuator, $homeostasisStates, $clock);
    $regulator = $homeostasis['thread_safe'](
        $homeostasis['modulate_state_history']($homeostasisActions)(
            $homeostasis['regulator']
        )
    );

    $amqp = amqp(logger('amqp', $amqpLog)(LogLevel::ERROR));
    $amqpClient = $amqp['client']['basic'](
        $amqpTransport,
        $amqpServer,
        new ElapsedPeriod(60000), // one minute
        $clock,
        $process,
        $remote
    );
    $exchanges = Set::of(
        Exchange\Declaration::class,
        Exchange\Declaration::durable('urls', Exchange\Type::direct())
    );
    $queues = Set::of(
        Queue\Declaration::class,
        Queue\Declaration::durable()->withName('crawler')
    );
    $bindings = Set::of(
        Queue\Binding::class,
        new Queue\Binding('urls', 'crawler')
    );

    $amqpClient = $amqp['client']['auto_declare']($exchanges, $queues, $bindings)(
        $amqp['client']['signal_aware'](
            $amqp['client']['logger'](
                $amqp['client']['fluent'](
                    $amqpClient
                )
            )
        )
    );
    $producer = $amqp['producers']($exchanges)($amqpClient)->get('urls');

    $tracer = new CrawlTracer\CrawlTracer($traces, $clock);
    $walker = new Walker;
    $robots = new RobotsTxt\KeepInMemoryParser(
        new RobotsTxt\CacheParser(
            new Parser(
                $transport,
                $walker,
                $userAgent
            ),
            $walker,
            $robots
        )
    );
    $halt = new Usleep;
    $delayer = new Delayer\ThresholdDelayer(
        new Delayer\RobotsTxtAwareDelayer(
            $robots,
            $userAgent,
            $halt,
            $clock
        ),
        new Delayer\TracerAwareDelayer(
            $tracer,
            new Delayer\FixDelayer(
                $halt,
                $clock
            ),
            $clock
        ),
        $clock
    );

    $crawler = new Crawler\XmlReaderAwareCrawler(
        $cacheStorage,
        new Crawler\TracerAwareCrawler(
            $tracer,
            new Crawler\RobotsAwareCrawler(
                $robots,
                new Crawler\DelayerAwareCrawler(
                    $delayer,
                    crawler(
                        new Transport\MemorySafe($transport),
                        $clock,
                        $reader,
                        $urlResolver
                    )
                ),
                $userAgent
            )
        )
    );

    $publisher = new Publisher\LinksAwarePublisher(
        new Publisher\ImagesAwarePublisher(
            new Publisher\AlternatesAwarePublisher(
                new Publisher\CanonicalAwarePublisher(
                    new Publisher\Publisher(
                        $rest,
                        new Translator\HttpResourceTranslator(
                            new Translator\Property\DelegationTranslator(
                                (new Map('string', Translator\PropertyTranslator::class))
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
                            )
                        )
                    ),
                    $producer
                ),
                $producer
            ),
            $producer
        ),
        $producer
    );

    $linker = new Linker\ReferrerLinker(
        new Linker\Linker($rest)
    );

    $consumers = (new Map('string', 'callable'))
        ->put('crawler', new AMQP\Consumer\CrawlConsumer(
            $crawler,
            $publisher,
            $linker,
            $userAgent
        ));

    $clients = monitor()['client'];

    return new Commands(
        new Command\Consume(
            $amqp['command']['consume']($consumers)($amqpClient),
            $regulator
        ),
        new Command\Crawl(
            $crawler,
            $userAgent,
            $publisher
        ),
        new Command\Install(
            $clients['silence'](
                $clients['socket']()
            )
        )
    );
}
