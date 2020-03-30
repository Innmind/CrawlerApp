<?php
declare(strict_types = 1);

namespace Tests\Crawler;

use function Crawler\bootstrap;
use Crawler\Command;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Url\{
    Url,
    Path,
};
use Innmind\Filesystem\Adapter;
use Innmind\Socket\Internet\Transport;
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\Server\Status\Server;
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    public function testBootstrap()
    {
        $os = $this->createMock(OperatingSystem::class);
        $os
            ->expects($this->any())
            ->method('status')
            ->willReturn($status = $this->createMock(Server::class));
        $status
            ->expects($this->any())
            ->method('tmp')
            ->willReturn(Path::of(\getcwd().'/var/'));

        $commands = bootstrap(
            $os,
            Url::of('file:///tmp/app.log'),
            Url::of('file:///tmp/amqp.log'),
            $this->createMock(Adapter::class),
            $this->createMock(Adapter::class),
            $this->createMock(Adapter::class),
            $this->createMock(Adapter::class),
            $this->createMock(Adapter::class),
            $this->createMock(Adapter::class),
            Path::of('/tmp'),
            Transport::tcp(),
            Url::of('amqp://user:pwd@localhost:5672/'),
            'apikey',
            'Innmind Robot'
        );

        $this->assertInstanceOf(Command\Consume::class, $commands[0]);
        $this->assertInstanceOf(Command\Crawl::class, $commands[1]);
        $this->assertInstanceOf(Command\Install::class, $commands[2]);
    }
}
