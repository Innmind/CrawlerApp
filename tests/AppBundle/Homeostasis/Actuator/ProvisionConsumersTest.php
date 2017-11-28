<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Homeostasis\Actuator;

use AppBundle\Homeostasis\Actuator\ProvisionConsumers;
use Innmind\Homeostasis\{
    Actuator,
    State
};
use Innmind\Server\Status\{
    Server as Status,
    Server\Processes,
    Server\Process,
    Server\Process\Pid,
    Server\Process\User,
    Server\Process\Memory,
    Server\Process\Command,
    Server\Cpu\Percentage
};
use Innmind\Server\Control\{
    Server as Control,
    Server\Processes as ControlProcesses,
    Server\Signal
};
use Innmind\TimeContinuum\PointInTimeInterface;
use Innmind\Immutable\{
    Map,
    Stream
};
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class ProvisionConsumersTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Actuator::class,
            new ProvisionConsumers(
                $this->createMock(Status::class),
                $this->createMock(Control::class),
                $this->createMock(LoggerInterface::class),
                'app',
                'test'
            )
        );
    }

    public function testDramaticDecreaseWhenNoProcesses()
    {
        $provisioner = new ProvisionConsumers(
            $status = $this->createMock(Status::class),
            $control = $this->createMock(Control::class),
            $logger = $this->createMock(LoggerInterface::class),
            'app',
            'test'
        );
        $status
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('all')
            ->willReturn(new Map('int', Process::class));
        $control
            ->expects($this->never())
            ->method('processes');
        $logger
            ->expects($this->once())
            ->method('alert')
            ->with('Dramatic decrease asked without consumers running');

        $this->assertNull($provisioner->dramaticDecrease(new Stream(State::class)));
    }

    public function testDramaticDecrease()
    {
        $provisioner = new ProvisionConsumers(
            $status = $this->createMock(Status::class),
            $control = $this->createMock(Control::class),
            $logger = $this->createMock(LoggerInterface::class),
            'app',
            'test'
        );
        $status
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('all')
            ->willReturn(
                (new Map('int', Process::class))
                    ->put(
                        2,
                        new Process(
                            new Pid(2),
                            new User('me'),
                            new Percentage(10),
                            new Memory(42),
                            $this->createMock(PointInTimeInterface::class),
                            new Command("php './console' '--env=test' 'innmind:amqp:consume' 'crawler' '50' '5'")
                        )
                    )
                    ->put(
                        3,
                        new Process(
                            new Pid(3),
                            new User('me'),
                            new Percentage(30),
                            new Memory(42),
                            $this->createMock(PointInTimeInterface::class),
                            new Command("php './console' '--env=test' 'innmind:amqp:consume' 'crawler' '50' '5'")
                        )
                    )
                    ->put(
                        4,
                        new Process(
                            new Pid(4),
                            new User('me'),
                            new Percentage(30),
                            new Memory(42),
                            $this->createMock(PointInTimeInterface::class),
                            new Command("php './console' '--env=test' 'innmind:amqp:consume' 'crawler' '50' '5'")
                        )
                    )
                    ->put(
                        5,
                        new Process(
                            new Pid(5),
                            new User('me'),
                            new Percentage(10),
                            new Memory(42),
                            $this->createMock(PointInTimeInterface::class),
                            new Command("php './console' '--env=test' 'innmind:amqp:consume' 'crawler' '50' '5'")
                        )
                    )
                    ->put(
                        6,
                        new Process(
                            new Pid(6),
                            new User('me'),
                            new Percentage(10),
                            new Memory(42),
                            $this->createMock(PointInTimeInterface::class),
                            new Command("php './console' '--env=test' 'innmind:amqp:consume' 'crawler' '50' '5'")
                        )
                    )
            );
        $control
            ->expects($this->exactly(2))
            ->method('processes')
            ->willReturn($processes = $this->createMock(ControlProcesses::class));
        $processes
            ->expects($this->at(0))
            ->method('kill')
            ->with(
                $this->callback(static function($pid): bool {
                    return $pid->toInt() === 4;
                }),
                Signal::terminate()
            );
        $processes
            ->expects($this->at(1))
            ->method('kill')
            ->with(
                $this->callback(static function($pid): bool {
                    return $pid->toInt() === 3;
                }),
                Signal::terminate()
            );

        $this->assertNull($provisioner->dramaticDecrease(new Stream(State::class)));
    }

    public function testDecrease()
    {
        $provisioner = new ProvisionConsumers(
            $status = $this->createMock(Status::class),
            $control = $this->createMock(Control::class),
            $logger = $this->createMock(LoggerInterface::class),
            'app',
            'test'
        );
        $status
            ->expects($this->never())
            ->method('processes');
        $control
            ->expects($this->never())
            ->method('processes');

        $this->assertNull($provisioner->decrease(new Stream(State::class)));
    }

    public function testHoldSteady()
    {
        $provisioner = new ProvisionConsumers(
            $status = $this->createMock(Status::class),
            $control = $this->createMock(Control::class),
            $logger = $this->createMock(LoggerInterface::class),
            'app',
            'test'
        );
        $status
            ->expects($this->never())
            ->method('processes');
        $control
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(ControlProcesses::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with(
                $this->callback(static function($command): bool {
                    return (string) $command === "php './console' '--env=test' 'innmind:amqp:consume' 'crawler' '50' '5'" &&
                        $command->workingDirectory() === 'app';
                })
            );

        $this->assertNull($provisioner->holdSteady(new Stream(State::class)));
    }

    public function testIncreaseWhenNoProcesses()
    {
        $provisioner = new ProvisionConsumers(
            $status = $this->createMock(Status::class),
            $control = $this->createMock(Control::class),
            $logger = $this->createMock(LoggerInterface::class),
            'app',
            'test'
        );
        $status
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('all')
            ->willReturn(new Map('int', Process::class));
        $control
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(ControlProcesses::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with(
                $this->callback(static function($command): bool {
                    return (string) $command === "php './console' '--env=test' 'innmind:amqp:consume' 'crawler' '50' '5'" &&
                        $command->workingDirectory() === 'app';
                })
            );

        $this->assertNull($provisioner->increase(new Stream(State::class)));
    }

    public function testIncrease()
    {
        $provisioner = new ProvisionConsumers(
            $status = $this->createMock(Status::class),
            $control = $this->createMock(Control::class),
            $logger = $this->createMock(LoggerInterface::class),
            'app',
            'test'
        );
        $status
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('all')
            ->willReturn(
                (new Map('int', Process::class))
                    ->put(
                        2,
                        new Process(
                            new Pid(2),
                            new User('me'),
                            new Percentage(10),
                            new Memory(42),
                            $this->createMock(PointInTimeInterface::class),
                            new Command("php './console' '--env=test' 'innmind:amqp:consume' 'crawler' '50' '5'")
                        )
                    )
                    ->put(
                        3,
                        new Process(
                            new Pid(3),
                            new User('me'),
                            new Percentage(10),
                            new Memory(42),
                            $this->createMock(PointInTimeInterface::class),
                            new Command('top')
                        )
                    )
                    ->put(
                        4,
                        new Process(
                            new Pid(4),
                            new User('me'),
                            new Percentage(10),
                            new Memory(42),
                            $this->createMock(PointInTimeInterface::class),
                            new Command("php './console' '--env=test' 'innmind:amqp:consume' 'crawler' '50' '5'")
                        )
                    )
            );
        $control
            ->expects($this->exactly(2))
            ->method('processes')
            ->willReturn($processes = $this->createMock(ControlProcesses::class));
        $processes
            ->expects($this->exactly(2))
            ->method('execute')
            ->with(
                $this->callback(static function($command): bool {
                    return (string) $command === "php './console' '--env=test' 'innmind:amqp:consume' 'crawler' '50' '5'" &&
                        $command->workingDirectory() === 'app';
                })
            );

        $this->assertNull($provisioner->increase(new Stream(State::class)));
    }

    public function testDramaticIncreaseWhenNoProcesses()
    {
        $provisioner = new ProvisionConsumers(
            $status = $this->createMock(Status::class),
            $control = $this->createMock(Control::class),
            $logger = $this->createMock(LoggerInterface::class),
            'app',
            'test'
        );
        $status
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('all')
            ->willReturn(new Map('int', Process::class));
        $control
            ->expects($this->exactly(2))
            ->method('processes')
            ->willReturn($processes = $this->createMock(ControlProcesses::class));
        $processes
            ->expects($this->exactly(2))
            ->method('execute')
            ->with(
                $this->callback(static function($command): bool {
                    return (string) $command === "php './console' '--env=test' 'innmind:amqp:consume' 'crawler' '50' '5'" &&
                        $command->workingDirectory() === 'app';
                })
            );

        $this->assertNull($provisioner->dramaticIncrease(new Stream(State::class)));
    }

    public function testDramaticIncrease()
    {
        $provisioner = new ProvisionConsumers(
            $status = $this->createMock(Status::class),
            $control = $this->createMock(Control::class),
            $logger = $this->createMock(LoggerInterface::class),
            'app',
            'test'
        );
        $status
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('all')
            ->willReturn(
                (new Map('int', Process::class))
                    ->put(
                        2,
                        new Process(
                            new Pid(2),
                            new User('me'),
                            new Percentage(10),
                            new Memory(42),
                            $this->createMock(PointInTimeInterface::class),
                            new Command("php './console' '--env=test' 'innmind:amqp:consume' 'crawler' '50' '5'")
                        )
                    )
                    ->put(
                        3,
                        new Process(
                            new Pid(3),
                            new User('me'),
                            new Percentage(10),
                            new Memory(42),
                            $this->createMock(PointInTimeInterface::class),
                            new Command('top')
                        )
                    )
                    ->put(
                        4,
                        new Process(
                            new Pid(4),
                            new User('me'),
                            new Percentage(10),
                            new Memory(42),
                            $this->createMock(PointInTimeInterface::class),
                            new Command("php './console' '--env=test' 'innmind:amqp:consume' 'crawler' '50' '5'")
                        )
                    )
            );
        $control
            ->expects($this->exactly(4))
            ->method('processes')
            ->willReturn($processes = $this->createMock(ControlProcesses::class));
        $processes
            ->expects($this->exactly(4))
            ->method('execute')
            ->with(
                $this->callback(static function($command): bool {
                    return (string) $command === "php './console' '--env=test' 'innmind:amqp:consume' 'crawler' '50' '5'" &&
                        $command->workingDirectory() === 'app';
                })
            );

        $this->assertNull($provisioner->dramaticIncrease(new Stream(State::class)));
    }
}
