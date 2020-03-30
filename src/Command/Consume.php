<?php
declare(strict_types = 1);

namespace Crawler\Command;

use Crawler\Homeostasis\Regulator\Regulate;
use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
};
use Innmind\Immutable\Str;

final class Consume implements Command
{
    private Command $consume;
    private Regulate $regulate;

    public function __construct(Command $consume, Regulate $regulate)
    {
        $this->consume = $consume;
        $this->regulate = $regulate;
    }

    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
    {
        ($this->consume)($env, $arguments, $options);

        if ($env->exitCode()->successful()) {
            ($this->regulate)();
        }
    }

    public function toString(): string
    {
        return Str::of($this->consume->toString())
            ->replace(
                'innmind:amqp:consume',
                'consume'
            )
            ->toString();
    }
}
