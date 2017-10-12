<?php
declare(strict_types = 1);

namespace AppBundle\EventListener;

use Innmind\Homeostasis\Regulator;
use Symfony\Component\{
    EventDispatcher\EventSubscriberInterface,
    Console\ConsoleEvents,
    Console\Event\ConsoleTerminateEvent
};

final class Regulate implements EventSubscriberInterface
{
    private $regulate;

    public function __construct(Regulator $regulator)
    {
        $this->regulate = $regulator;
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

        ($this->regulate)();
    }
}
