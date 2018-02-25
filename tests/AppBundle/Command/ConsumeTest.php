<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Command;

use AppBundle\Command\Consume;
use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
    Environment\ExitCode,
};
use Innmind\Homeostasis\{
    Regulator,
    Strategy,
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
                $this->createMock(Regulator::class)
            )
        );
    }

    public function testStringCast()
    {
        $command = new Consume(
            $mock = $this->createMock(Command::class),
            $this->createMock(Regulator::class)
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
            $regulator = $this->createMock(Regulator::class)
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
        $regulator
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn(Strategy::holdSteady());

        $this->assertNull($consume($env, $arguments, $options));
    }

    public function testDoesntRegulateOnError()
    {
        $consume = new Consume(
            $mock = $this->createMock(Command::class),
            $regulator = $this->createMock(Regulator::class)
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
        $regulator
            ->expects($this->never())
            ->method('__invoke');

        $this->assertNull($consume($env, $arguments, $options));
    }
}
