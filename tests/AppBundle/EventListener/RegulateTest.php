<?php
declare(strict_types = 1);

namespace Tests\AppBundle\EventListener;

use AppBundle\EventListener\Regulate;
use Innmind\Homeostasis\{
    Regulator,
    Strategy
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
            new Regulate($this->createMock(Regulator::class))
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
            $regulator = $this->createMock(Regulator::class)
        );
        $regulator
            ->expects($this->never())
            ->method('__invoke');
        $event = new ConsoleTerminateEvent(
            new Command('foo'),
            $this->createMock(InputInterface::class),
            $this->createMock(OutputInterface::class),
            0
        );

        $this->assertNull($regulate($event));
    }

    public function testHandleEvent()
    {
        $regulate = new Regulate(
            $regulator = $this->createMock(Regulator::class)
        );
        $regulator
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn(Strategy::holdSteady());
        $event = new ConsoleTerminateEvent(
            new Command('rabbitmq:consumer'),
            $this->createMock(InputInterface::class),
            $this->createMock(OutputInterface::class),
            0
        );

        $this->assertNull($regulate($event));
    }
}
