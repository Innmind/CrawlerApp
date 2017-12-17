<?php
declare(strict_types = 1);

namespace Tests\AppBundle\EventListener;

use AppBundle\EventListener\Regulate;
use Innmind\Homeostasis\{
    Regulator,
    Strategy,
    Actuator,
    Exception\HomeostasisAlreadyInProcess
};
use Symfony\Component\{
    EventDispatcher\EventSubscriberInterface,
    Console\ConsoleEvents,
    Console\Event\ConsoleTerminateEvent,
    Console\Command\Command,
    Console\Input\InputInterface,
    Console\Output\OutputInterface
};
use PHPUnit\Framework\TestCase;

class RegulateTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            EventSubscriberInterface::class,
            new Regulate(
                $this->createMock(Regulator::class),
                $this->createMock(Actuator::class)
            )
        );
    }

    public function testGetSubscribedEvents()
    {
        $this->assertSame(
            [ConsoleEvents::TERMINATE => '__invoke'],
            Regulate::getSubscribedEvents()
        );
    }

    public function testDoesntHandleEvent()
    {
        $regulate = new Regulate(
            $regulator = $this->createMock(Regulator::class),
            $actuator = $this->createMock(Actuator::class)
        );
        $regulator
            ->expects($this->never())
            ->method('__invoke');
        $actuator
            ->expects($this->never())
            ->method('holdSteady');
        $event = new ConsoleTerminateEvent(
            new Command('foo'),
            $this->createMock(InputInterface::class),
            $this->createMock(OutputInterface::class),
            0
        );

        $this->assertNull($regulate($event));
    }

    public function testDoesntThrowWhenHomeostasisAlreadyInProcess()
    {
        $regulate = new Regulate(
            $regulator = $this->createMock(Regulator::class),
            $actuator = $this->createMock(Actuator::class)
        );
        $regulator
            ->expects($this->once())
            ->method('__invoke')
            ->will($this->throwException(new HomeostasisAlreadyInProcess));
        $actuator
            ->expects($this->once())
            ->method('holdSteady');
        $event = new ConsoleTerminateEvent(
            new Command('innmind:amqp:consume'),
            $this->createMock(InputInterface::class),
            $this->createMock(OutputInterface::class),
            0
        );

        $this->assertNull($regulate($event));
    }

    public function testHandleEvent()
    {
        $regulate = new Regulate(
            $regulator = $this->createMock(Regulator::class),
            $actuator = $this->createMock(Actuator::class)
        );
        $regulator
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn(Strategy::holdSteady());
        $actuator
            ->expects($this->never())
            ->method('holdSteady');
        $event = new ConsoleTerminateEvent(
            new Command('innmind:amqp:consume'),
            $this->createMock(InputInterface::class),
            $this->createMock(OutputInterface::class),
            0
        );

        $this->assertNull($regulate($event));
    }
}
