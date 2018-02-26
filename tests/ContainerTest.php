<?php
declare(strict_types = 1);

namespace Tests;

use Innmind\CLI\Commands;
use Innmind\Compose\ContainerBuilder\ContainerBuilder;
use Innmind\Url\{
    Url,
    Path,
};
use Innmind\Socket\Internet\Transport;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    public function testContainer()
    {
        $container = (new ContainerBuilder)(
            new Path('config/container.yml'),
            (new Map('string', 'mixed'))
                ->put('amqpLogPath', '/tmp/logs/amqp.log')
                ->put('defaultLogPath', '/tmp/logs/app.log')
                ->put('userAgent', 'Innmind Robot')
                ->put('workingDirectory', '/tmp')
                ->put('logDirectory', '/tmp/logs')
                ->put('environment', 'test')
                ->put('amqpTransport', Transport::tcp())
                ->put('amqpServer', Url::fromString('amqp://user:pwd@localhost:5672/'))
                ->put('stateDirectory', '/tmp/state')
                ->put('actionDirectory', '/tmp/action')
        );

        $this->assertInstanceOf(Commands::class, $container->get('commands'));
    }
}
