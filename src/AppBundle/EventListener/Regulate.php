<?php
declare(strict_types = 1);

namespace AppBundle\EventListener;

use Innmind\Homeostasis\{
    Regulator,
    State,
    Actuator,
    Exception\HomeostasisAlreadyInProcess
};
use Innmind\Immutable\Stream;
use Symfony\Component\{
    EventDispatcher\EventSubscriberInterface,
    Console\ConsoleEvents,
    Console\Event\ConsoleTerminateEvent
};

final class Regulate implements EventSubscriberInterface
{
    private $regulate;
    private $actuator;

    public function __construct(
        Regulator $regulator,
        Actuator $actuator
    ) {
        $this->regulate = $regulator;
        $this->actuator = $actuator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::TERMINATE => '__invoke',
        ];
    }

    public function __invoke(ConsoleTerminateEvent $event): void
    {
        $command = $event->getCommand();

        if ($command->getName() !== 'innmind:amqp:consume') {
            return;
        }

        try {
            ($this->regulate)();
        } catch (HomeostasisAlreadyInProcess $e) {
            $this->actuator->holdSteady(Stream::of(State::class));
        }
    }
}
