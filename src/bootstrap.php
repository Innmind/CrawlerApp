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
use function Innmind\Stack\stack;
use function Innmind\IPC\bootstrap as ipc;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Url\{
    UrlInterface,
    PathInterface,
    Path,
};
use Innmind\TimeContinuum\{
    ElapsedPeriod,
    Period\Earth\Second,
};
use Innmind\UrlResolver\UrlResolver;
use Innmind\Filesystem\Adapter;
use Innmind\Homeostasis\Factor;
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
use Innmind\IPC\Process\Name;
use Innmind\Immutable\{
    Set,
    Map,
};
use Psr\Log\LogLevel;

function bootstrap(
    OperatingSystem $os,
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
): array {
    $logger = logger('app', $appLog)(LogLevel::ERROR);
    $transport = transport();
    $log = $transport['logger']($logger);
    $transport = stack(
        $transport['throw_on_error'],
        $log
    )($os->remote()->http());

    $xml = xml();
    $reader = $xml['cache'](html());
    $cacheStorage = $xml['cache_storage'];
    $urlResolver = new UrlResolver;
    $serverStatus = ServerStatusFactory::build($os->clock());

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
        Homeostasis\Factors::cpu($os->clock(), $serverStatus),
        Homeostasis\Factors::log(
            $os->clock(),
            new Synchronous(new Symfony($os->clock())),
            $logs
        )
    );
    $actuator = new Homeostasis\Actuator\ProvisionConsumers(
        $serverStatus,
        ServerControlFactory::build(),
        $logger,
        (string) $workingDirectory
    );

    $homeostasis = homeostasis($factors, $actuator, $homeostasisStates, $os->clock());
    $regulator = $homeostasis['modulate_state_history']($homeostasisActions)(
        $homeostasis['regulator']
    );

    $amqp = amqp(logger('amqp', $amqpLog)(LogLevel::ERROR));
    $amqpClient = $amqp['client']['basic'](
        $amqpTransport,
        $amqpServer,
        new ElapsedPeriod(60000), // one minute
        $os->clock(),
        $os->process(),
        $os->remote()
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

    $amqpClient = stack(
        $amqp['client']['auto_declare']($exchanges, $queues, $bindings),
        static function($client) use ($amqp, $os) {
            return $amqp['client']['signal_aware']($client, $os->process()->signals());
        },
        $amqp['client']['logger'],
        $amqp['client']['fluent']
    )($amqpClient);
    $producer = $amqp['producers']($exchanges)($amqpClient)->get('urls');

    $tracer = new CrawlTracer\CrawlTracer($traces, $os->clock());
    $walker = new Walker;
    $robots = new RobotsTxt\KeepInMemoryParser(
        new RobotsTxt\CacheParser(
            new Parser(
                $log($os->remote()->http()),
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
            $os->clock()
        ),
        new Delayer\TracerAwareDelayer(
            $tracer,
            new Delayer\FixDelayer(
                $os->process()
            ),
            $os->clock()
        ),
        $os->clock()
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
                        $os->clock(),
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
                                Map::of('string', Translator\PropertyTranslator::class)
                                    ('host', new Translator\Property\HostTranslator)
                                    ('path', new Translator\Property\PathTranslator)
                                    ('query', new Translator\Property\QueryTranslator)
                                    ('languages', new Translator\Property\LanguagesTranslator)
                                    ('charset', new Translator\Property\CharsetTranslator)
                                    ('dimension', new Translator\Property\Image\DimensionTranslator)
                                    ('weight', new Translator\Property\Image\WeightTranslator)
                                    ('anchors', new Translator\Property\HtmlPage\AnchorsTranslator)
                                    ('android', new Translator\Property\HtmlPage\AndroidAppLinkTranslator)
                                    ('description', new Translator\Property\HtmlPage\DescriptionTranslator)
                                    ('ios', new Translator\Property\HtmlPage\IosAppLinkTranslator)
                                    ('journal', new Translator\Property\HtmlPage\IsJournalTranslator)
                                    ('mainContent', new Translator\Property\HtmlPage\MainContentTranslator)
                                    ('themeColour', new Translator\Property\HtmlPage\ThemeColourTranslator)
                                    ('title', new Translator\Property\HtmlPage\TitleTranslator)
                                    ('preview', new Translator\Property\HtmlPage\PreviewTranslator)
                                    ('author', new Translator\Property\HtmlPage\AuthorTranslator)
                                    ('citations', new Translator\Property\HtmlPage\CitationsTranslator)
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

    $consumers = Map::of('string', 'callable')
        ('crawler', new AMQP\Consumer\CrawlConsumer(
            $crawler,
            $publisher,
            $linker,
            $userAgent
        ));

    $clients = monitor($os)['client'];

    $ipc = ipc($os);
    $homeostasis = new Name('crawler-homeostasis');

    return [
        new Command\Consume(
            $amqp['command']['consume']($consumers)($amqpClient),
            new Homeostasis\Regulator\Regulate(
                $ipc,
                $homeostasis
            )
        ),
        new Command\Crawl(
            $crawler,
            $userAgent,
            $publisher
        ),
        new Command\Install(
            $clients['silence'](
                $clients['ipc']()
            ),
            $os->filesystem()->mount(new Path(__DIR__.'/../config'))
        ),
        new Command\Homeostasis(
            $ipc->listen($homeostasis),
            $regulator,
            $os->control()->processes()
        )
    ];
}
