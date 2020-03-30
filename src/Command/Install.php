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
    Name,
};
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\Str;

final class Install implements Command
{
    private Client $client;
    private Adapter $config;

    public function __construct(Client $client, Adapter $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
    {
        if ($this->config->contains(new Name('.env'))) {
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
                return $event->name()->toString();
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

        /** @var string */
        $amqpPassword = $events
            ->get('amqp.user_added')
            ->filter(static function(Event $event): bool {
                return $event->payload()->get('name') === 'consumer';
            })
            ->first()
            ->payload()
            ->get('password');

        /** @var string */
        $apiKey = $events
            ->get('library_installed')
            ->first()
            ->payload()
            ->get('apiKey');
        $envVars = "API_KEY=$apiKey\n";
        $envVars .= "AMQP_SERVER=amqp://consumer:$amqpPassword@localhost:5672/";

        $this->config->add(new File(
            new Name('.env'),
            Stream::ofContent($envVars)
        ));
    }

    public function toString(): string
    {
        return <<<USAGE
install

This will configure the config/.env file

It will do so by reading events recorded by the installation monitor
USAGE;
    }
}
