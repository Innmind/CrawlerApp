<?php
declare(strict_types = 1);

namespace AppBundle\Homeostasis\Regulator;

use Innmind\Homeostasis\{
    Regulator,
    Actuator,
    Strategy,
    State,
    Exception\HomeostasisAlreadyInProcess,
};
use Innmind\Immutable\Stream;

final class Regulate implements Regulator
{
    private $regulate;
    private $actuator;

    public function __construct(Regulator $regulator, Actuator $actuator)
    {
        $this->regulate = $regulator;
        $this->actuator = $actuator;
    }

    public function __invoke(): Strategy
    {
        try {
            return ($this->regulate)();
        } catch (HomeostasisAlreadyInProcess $e) {
            $this->actuator->holdSteady(Stream::of(State::class));
        }

        return Strategy::holdSteady();
    }
}
