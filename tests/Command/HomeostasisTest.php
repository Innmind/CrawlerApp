<?php
declare(strict_types = 1);

namespace Tests\Crawler\Command;

use Crawler\Command\Homeostasis;
use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
};
use Innmind\IPC\Server;
use Innmind\Homeostasis\{
    Regulator,
    Strategy,
};
use Innmind\Server\Control\Server\Processes;
use Innmind\Url\Path;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class HomeostasisTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Command::class,
            new Homeostasis(
                $this->createMock(Server::class),
                $this->createMock(Regulator::class),
                $this->createMock(Processes::class)
            )
        );
    }

    public function testUsage()
    {
        $this->assertSame(
            'homeostasis -d|--daemon',
            (new Homeostasis(
                $this->createMock(Server::class),
                $this->createMock(Regulator::class),
                $this->createMock(Processes::class)
            ))->toString()
        );
    }

    public function testRegulateOnEachMessage()
    {
        $command = new Homeostasis(
            $server = $this->createMock(Server::class),
            $regulator = $this->createMock(Regulator::class),
            $processes = $this->createMock(Processes::class)
        );
        $regulator
            ->expects($this->exactly(2))
            ->method('__invoke')
            ->willReturn(Strategy::holdSteady());
        $server
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function($listener) {
                // simulate 2 incoming messages
                $listener();
                $listener();

                return true;
            }));
        $processes
            ->expects($this->never())
            ->method('execute');

        $this->assertNull($command(
            $this->createMock(Environment::class),
            new Arguments,
            new Options
        ));
    }

    public function testStartDaemon()
    {
        $command = new Homeostasis(
            $server = $this->createMock(Server::class),
            $regulator = $this->createMock(Regulator::class),
            $processes = $this->createMock(Processes::class)
        );
        $server
            ->expects($this->never())
            ->method('__invoke');
        $regulator
            ->expects($this->never())
            ->method('__invoke');
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command) {
                return $command->toString() === "bin/crawler 'homeostasis'" &&
                    $command->workingDirectory()->toString() === '/somewhere' &&
                    $command->toBeRunInBackground();
            }));
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->once())
            ->method('workingDirectory')
            ->willReturn(Path::of('/somewhere'));

        $this->assertNull($command(
            $env,
            new Arguments,
            new Options(
                Map::of('string', 'string')
                    ('daemon', '')
            )
        ));
    }
}
