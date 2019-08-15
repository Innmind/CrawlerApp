<?php
declare(strict_types = 1);

namespace Crawler\Command;

use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
};
use Innmind\IPC\Server;
use Innmind\Homeostasis\Regulator;
use Innmind\Server\Control\Server\{
    Processes,
    Command as ServerCommand,
};

final class Homeostasis implements Command
{
    private $listen;
    private $regulate;
    private $processes;

    public function __construct(
        Server $listen,
        Regulator $regulate,
        Processes $processes
    ) {
        $this->listen = $listen;
        $this->regulate = $regulate;
        $this->processes = $processes;
    }

    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
    {
        if ($options->contains('daemon')) {
            $this
                ->processes
                ->execute(
                    ServerCommand::background('bin/crawler')
                        ->withArgument('homeostasis')
                        ->withWorkingDirectory((string) $env->workingDirectory())
                );

            return;
        }

        ($this->listen)(function() {
            ($this->regulate)();
        });
    }

    public function __toString(): string
    {
        return <<<USAGE
homeostasis -d|--daemon
USAGE;
    }
}
