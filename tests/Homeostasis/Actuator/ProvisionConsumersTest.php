<?php
declare(strict_types = 1);

namespace Tests\Crawler\Homeostasis\Actuator;

use Crawler\Homeostasis\Actuator\ProvisionConsumers;
use Innmind\Homeostasis\{
    Actuator,
    State,
};
use Innmind\Server\Status\{
    Server as Status,
    Server\Processes,
    Server\Process,
    Server\Process\Pid,
    Server\Process\User,
    Server\Process\Memory,
    Server\Process\Command,
    Server\Cpu\Percentage,
};
use Innmind\Server\Control\{
    Server as Control,
    Server\Processes as ControlProcesses,
    Server\Signal,
};
use Innmind\TimeContinuum\PointInTime;
use Innmind\Immutable\{
    Map,
    Sequence,
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
                'app'
            )
        );
    }

    public function testDramaticDecreaseWhenNoProcesses()
    {
        $provisioner = new ProvisionConsumers(
            $status = $this->createMock(Status::class),
            $control = $this->createMock(Control::class),
            $logger = $this->createMock(LoggerInterface::class),
            '/path/to/app'
        );
        $status
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('all')
            ->willReturn(Map::of('int', Process::class));
        $control
            ->expects($this->never())
            ->method('processes');
        $logger
            ->expects($this->once())
            ->method('alert')
            ->with('Dramatic decrease asked without consumers running');

        $this->assertNull($provisioner->dramaticDecrease(Sequence::of(State::class)));
    }

    public function testDramaticDecrease()
    {
        $provisioner = new ProvisionConsumers(
            $status = $this->createMock(Status::class),
            $control = $this->createMock(Control::class),
            $logger = $this->createMock(LoggerInterface::class),
            '/path/to/app'
        );
        $status
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('all')
            ->willReturn(
                Map::of('int', Process::class)
                    (
                        2,
                        new Process(
                            new Pid(2),
                            new User('me'),
                            new Percentage(10),
                            new Memory(42),
                            $this->createMock(PointInTime::class),
                            new Command('php ./bin/crawler consume crawler 50 5')
                        )
                    )
                    (
                        3,
                        new Process(
                            new Pid(3),
                            new User('me'),
                            new Percentage(30),
                            new Memory(42),
                            $this->createMock(PointInTime::class),
                            new Command('php ./bin/crawler consume crawler 50 5')
                        )
                    )
                    (
                        4,
                        new Process(
                            new Pid(4),
                            new User('me'),
                            new Percentage(30),
                            new Memory(42),
                            $this->createMock(PointInTime::class),
                            new Command('php ./bin/crawler consume crawler 50 5')
                        )
                    )
                    (
                        5,
                        new Process(
                            new Pid(5),
                            new User('me'),
                            new Percentage(10),
                            new Memory(42),
                            $this->createMock(PointInTime::class),
                            new Command('php ./bin/crawler consume crawler 50 5')
                        )
                    )
                    (
                        6,
                        new Process(
                            new Pid(6),
                            new User('me'),
                            new Percentage(10),
                            new Memory(42),
                            $this->createMock(PointInTime::class),
                            new Command('php ./bin/crawler consume crawler 50 5')
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
                    return $pid->toInt() === 3;
                }),
                Signal::terminate()
            );
        $processes
            ->expects($this->at(1))
            ->method('kill')
            ->with(
                $this->callback(static function($pid): bool {
                    return $pid->toInt() === 4;
                }),
                Signal::terminate()
            );

        $this->assertNull($provisioner->dramaticDecrease(Sequence::of(State::class)));
    }

    public function testDecrease()
    {
        $provisioner = new ProvisionConsumers(
            $status = $this->createMock(Status::class),
            $control = $this->createMock(Control::class),
            $logger = $this->createMock(LoggerInterface::class),
            '/path/to/app'
        );
        $status
            ->expects($this->never())
            ->method('processes');
        $control
            ->expects($this->never())
            ->method('processes');

        $this->assertNull($provisioner->decrease(Sequence::of(State::class)));
    }

    public function testHoldSteady()
    {
        $provisioner = new ProvisionConsumers(
            $status = $this->createMock(Status::class),
            $control = $this->createMock(Control::class),
            $logger = $this->createMock(LoggerInterface::class),
            '/path/to/app'
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
                    return $command->toString() === "php './bin/crawler' 'consume' 'crawler' '50' '5'" &&
                        $command->workingDirectory()->toString() === '/path/to/app';
                })
            );

        $this->assertNull($provisioner->holdSteady(Sequence::of(State::class)));
    }

    public function testIncreaseWhenNoProcesses()
    {
        $provisioner = new ProvisionConsumers(
            $status = $this->createMock(Status::class),
            $control = $this->createMock(Control::class),
            $logger = $this->createMock(LoggerInterface::class),
            '/path/to/app'
        );
        $status
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('all')
            ->willReturn(Map::of('int', Process::class));
        $control
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(ControlProcesses::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with(
                $this->callback(static function($command): bool {
                    return $command->toString() === "php './bin/crawler' 'consume' 'crawler' '50' '5'" &&
                        $command->workingDirectory()->toString() === '/path/to/app';
                })
            );

        $this->assertNull($provisioner->increase(Sequence::of(State::class)));
    }

    public function testIncrease()
    {
        $provisioner = new ProvisionConsumers(
            $status = $this->createMock(Status::class),
            $control = $this->createMock(Control::class),
            $logger = $this->createMock(LoggerInterface::class),
            '/path/to/app'
        );
        $status
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('all')
            ->willReturn(
                Map::of('int', Process::class)
                    (
                        2,
                        new Process(
                            new Pid(2),
                            new User('me'),
                            new Percentage(10),
                            new Memory(42),
                            $this->createMock(PointInTime::class),
                            new Command('php ./bin/crawler consume crawler 50 5')
                        )
                    )
                    (
                        3,
                        new Process(
                            new Pid(3),
                            new User('me'),
                            new Percentage(10),
                            new Memory(42),
                            $this->createMock(PointInTime::class),
                            new Command('top')
                        )
                    )
                    (
                        4,
                        new Process(
                            new Pid(4),
                            new User('me'),
                            new Percentage(10),
                            new Memory(42),
                            $this->createMock(PointInTime::class),
                            new Command('php ./bin/crawler consume crawler 50 5')
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
                    return $command->toString() === "php './bin/crawler' 'consume' 'crawler' '50' '5'" &&
                        $command->workingDirectory()->toString() === '/path/to/app';
                })
            );

        $this->assertNull($provisioner->increase(Sequence::of(State::class)));
    }

    public function testDramaticIncreaseWhenNoProcesses()
    {
        $provisioner = new ProvisionConsumers(
            $status = $this->createMock(Status::class),
            $control = $this->createMock(Control::class),
            $logger = $this->createMock(LoggerInterface::class),
            '/path/to/app'
        );
        $status
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('all')
            ->willReturn(Map::of('int', Process::class));
        $control
            ->expects($this->exactly(2))
            ->method('processes')
            ->willReturn($processes = $this->createMock(ControlProcesses::class));
        $processes
            ->expects($this->exactly(2))
            ->method('execute')
            ->with(
                $this->callback(static function($command): bool {
                    return $command->toString() === "php './bin/crawler' 'consume' 'crawler' '50' '5'" &&
                        $command->workingDirectory()->toString() === '/path/to/app';
                })
            );

        $this->assertNull($provisioner->dramaticIncrease(Sequence::of(State::class)));
    }

    public function testDramaticIncrease()
    {
        $provisioner = new ProvisionConsumers(
            $status = $this->createMock(Status::class),
            $control = $this->createMock(Control::class),
            $logger = $this->createMock(LoggerInterface::class),
            '/path/to/app'
        );
        $status
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('all')
            ->willReturn(
                Map::of('int', Process::class)
                    (
                        2,
                        new Process(
                            new Pid(2),
                            new User('me'),
                            new Percentage(10),
                            new Memory(42),
                            $this->createMock(PointInTime::class),
                            new Command('php ./bin/crawler consume crawler 50 5')
                        )
                    )
                    (
                        3,
                        new Process(
                            new Pid(3),
                            new User('me'),
                            new Percentage(10),
                            new Memory(42),
                            $this->createMock(PointInTime::class),
                            new Command('top')
                        )
                    )
                    (
                        4,
                        new Process(
                            new Pid(4),
                            new User('me'),
                            new Percentage(10),
                            new Memory(42),
                            $this->createMock(PointInTime::class),
                            new Command('php ./bin/crawler consume crawler 50 5')
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
                    return $command->toString() === "php './bin/crawler' 'consume' 'crawler' '50' '5'" &&
                        $command->workingDirectory()->toString() === '/path/to/app';
                })
            );

        $this->assertNull($provisioner->dramaticIncrease(Sequence::of(State::class)));
    }
}
