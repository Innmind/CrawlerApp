<?php
declare(strict_types = 1);

namespace Tests\Crawler\Command;

use Crawler\{
    Command\Consume,
    Homeostasis\Regulator\Regulate,
};
use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
    Environment\ExitCode,
};
use Innmind\IPC\{
    IPC,
    Process\Name,
};
use PHPUnit\Framework\TestCase;

class ConsumeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Command::class,
            new Consume(
                $this->createMock(Command::class),
                new Regulate(
                    $this->createMock(IPC::class),
                    new Name('foo')
                )
            )
        );
    }

    public function testStringCast()
    {
        $command = new Consume(
            $mock = $this->createMock(Command::class),
            new Regulate(
                $this->createMock(IPC::class),
                new Name('foo')
            )
        );
        $mock
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('innmind:amqp:consume queue [number] [prefetch]');

        $this->assertSame('consume queue [number] [prefetch]', (string) $command);
    }

    public function testInvokation()
    {
        $consume = new Consume(
            $mock = $this->createMock(Command::class),
            new Regulate(
                $ipc = $this->createMock(IPC::class),
                new Name('foo')
            )
        );
        $env = $this->createMock(Environment::class);
        $arguments = new Arguments;
        $options = new Options;
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($env, $arguments, $options);
        $env
            ->expects($this->once())
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $ipc
            ->expects($this->once())
            ->method('wait');

        $this->assertNull($consume($env, $arguments, $options));
    }

    public function testDoesntRegulateOnError()
    {
        $consume = new Consume(
            $mock = $this->createMock(Command::class),
            new Regulate(
                $ipc = $this->createMock(IPC::class),
                new Name('foo')
            )
        );
        $env = $this->createMock(Environment::class);
        $arguments = new Arguments;
        $options = new Options;
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($env, $arguments, $options);
        $env
            ->expects($this->once())
            ->method('exitCode')
            ->willReturn(new ExitCode(1));
        $ipc
            ->expects($this->never())
            ->method('wait');

        $this->assertNull($consume($env, $arguments, $options));
    }
}
