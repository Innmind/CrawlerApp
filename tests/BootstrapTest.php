<?php
declare(strict_types = 1);

namespace Tests\Crawler;

use function Crawler\bootstrap;
use Innmind\Url\{
    Url,
    Path,
};
use Innmind\Filesystem\Adapter;
use Innmind\Socket\Internet\Transport;
use Innmind\CLI\Commands;
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    public function testBootstrap()
    {
        $commands = bootstrap(
            Url::fromString('file:///tmp/app.log'),
            Url::fromString('file:///tmp/amqp.log'),
            $this->createMock(Adapter::class),
            $this->createMock(Adapter::class),
            $this->createMock(Adapter::class),
            $this->createMock(Adapter::class),
            $this->createMock(Adapter::class),
            $this->createMock(Adapter::class),
            new Path('/tmp'),
            Transport::tcp(),
            Url::fromString('amqp://user:pwd@localhost:5672/'),
            'Innmind Robot'
        );

        $this->assertInstanceOf(Commands::class, $commands);
    }
}
