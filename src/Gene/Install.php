<?php
declare(strict_types = 1);

namespace Crawler\Gene;

use Innmind\Genome\{
    Gene,
    History,
    History\Event,
    Exception\PreConditionFailed,
    Exception\ExpressionFailed,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Server\Control\{
    Server,
    Server\Command,
    Server\Script,
    Exception\ScriptFailed,
};
use Innmind\Url\Path;

final class Install implements Gene
{
    private Path $path;

    public function __construct(Path $path)
    {
        $this->path = $path;
    }

    public function name(): string
    {
        return 'Crawler install';
    }

    public function express(
        OperatingSystem $local,
        Server $target,
        History $history
    ): History {
        try {
            $preCondition = new Script(
                Command::foreground('which')->withArgument('composer'),
            );
            $preCondition($target);
        } catch (ScriptFailed $e) {
            throw new PreConditionFailed('composer is missing');
        }

        $library = $history->get('library_installed');

        if ($library->empty()) {
            throw new PreConditionFailed('No library installed');
        }

        $amqp = $history->get('amqp.user_added');

        if ($amqp->empty()) {
            throw new PreConditionFailed('No amqp user provided');
        }

        /**
         * @psalm-suppress UnusedClosureParam
         * @var Event
         */
        $library = $library->reduce(
            null,
            static fn(?Event $last, Event $event): Event => $event,
        );
        /** @var Event */
        $amqp = $amqp->reduce(
            null,
            static fn(?Event $last, Event $event): ?Event => $last ?? ($event->payload()->get('name') === 'consumer' ? $event : null),
        );
        /** @var string */
        $apiKey = $library->payload()->get('apiKey');
        /** @var string */
        $password = $amqp->payload()->get('password');

        $dotEnv = <<<DOTENV
        API_KEY=$apiKey
        AMQP_SERVER=amqp://consumer:$password@localhost:5672/
        DOTENV;

        try {
            $install = new Script(
                Command::foreground('composer')
                    ->withArgument('create-project')
                    ->withArgument('innmind/crawler-app')
                    ->withArgument($this->path->toString())
                    ->withOption('no-dev')
                    ->withOption('prefer-source')
                    ->withOption('keep-vcs'),
                Command::foreground('echo')
                    ->withArgument($dotEnv)
                    ->overwrite($this->path->resolve(Path::of('config/.env'))),
                Command::foreground('bin/crawler')
                    ->withArgument('homeostasis')
                    ->withOption('daemon')
                    ->withWorkingDirectory($this->path),
            );
            $install($target);
        } catch (ScriptFailed $e) {
            throw new ExpressionFailed($this->name());
        }

        return $history;
    }
}
