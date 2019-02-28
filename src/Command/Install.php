<?php
declare(strict_types = 1);

namespace Crawler\Command;

use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
};
use Innmind\InstallationMonitor\{
    Client,
    Event,
};
use Innmind\Filesystem\{
    Adapter,
    File\File,
    Stream\StringStream,
};
use Innmind\Immutable\{
    Map,
    Str,
    SequenceInterface,
    Sequence,
};

final class Install implements Command
{
    private $client;
    private $config;

    public function __construct(Client $client, Adapter $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
    {
        if ($this->config->has('.env')) {
            $env->error()->write(
                Str::of("App already installed\n")
            );
            $env->exit(1);

            return;
        }

        $events = $this
            ->client
            ->events()
            ->groupBy(static function(Event $event): string {
                return (string) $event->name();
            });

        if (
            !$events->contains('library_installed') ||
            !$events->contains('amqp.user_added')
        ) {
            $env->error()->write(
                Str::of("Configuration can't be determined\n")
            );
            $env->exit(1);

            return;
        }

        $amqpPassword = $events
            ->get('amqp.user_added')
            ->filter(static function(Event $event): bool {
                return $event->payload()->get('name') === 'consumer';
            })
            ->first()
            ->payload()
            ->get('password');

        $envVars = (string) (new Map('string', 'string'))
            ->put(
                'API_KEY',
                $events
                    ->get('library_installed')
                    ->first()
                    ->payload()
                    ->get('apiKey')
            )
            ->put('AMQP_SERVER', "amqp://consumer:$amqpPassword@localhost:5672/")
            ->reduce(
                new Sequence,
                static function(SequenceInterface $lines, string $key, string $value): SequenceInterface {
                    return $lines->add(sprintf(
                        '%s=%s',
                        $key,
                        $value
                    ));
                }
            )
            ->join("\n");

        $this->config->add(new File(
            '.env',
            new StringStream($envVars)
        ));
    }

    public function __toString(): string
    {
        return <<<USAGE
install

This will configure the config/.env file

It will do so by reading events recorded by the installation monitor
USAGE;
    }
}
