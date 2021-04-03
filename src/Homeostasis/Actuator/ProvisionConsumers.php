<?php
declare(strict_types = 1);

namespace Crawler\Homeostasis\Actuator;

use Innmind\Homeostasis\Actuator;
use Innmind\Server\Status\{
    Server as Status,
    Server\Process,
};
use Innmind\Server\Control\{
    Server as Control,
    Server\Command,
    Server\Signal,
    Server\Process\Pid,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Sequence,
    Map,
    Str,
};
use Psr\Log\LoggerInterface;

final class ProvisionConsumers implements Actuator
{
    private Status $status;
    private Control $control;
    private LoggerInterface $logger;
    private Command $spawn;

    public function __construct(
        Status $status,
        Control $control,
        LoggerInterface $logger,
        string $workingDirectory
    ) {
        $this->status = $status;
        $this->control = $control;
        $this->logger = $logger;
        $this->spawn = Command::background('php')
            ->withArgument('./bin/crawler')
            ->withArgument('consume')
            ->withArgument('crawler')
            ->withArgument('50')
            ->withArgument('5')
            ->withWorkingDirectory(Path::of($workingDirectory));
    }

    public function dramaticDecrease(Sequence $states): void
    {
        $running = $this->processes();

        if ($running->count() === 0) {
            $this->logger->alert('Dramatic decrease asked without consumers running');

            return;
        }

        $running
            ->values()
            ->sort(static function(Process $a, Process $b): int {
                return $b->cpu()->toFloat() <=> $a->cpu()->toFloat();
            })
            ->take((int) ($running->count() / 2))
            ->foreach(function(Process $process): void {
                $this
                    ->control
                    ->processes()
                    ->kill(
                        new Pid($process->pid()->toInt()),
                        Signal::terminate()
                    );
            });
    }

    public function decrease(Sequence $states): void
    {
        //let the consumers finish by themselves
    }

    public function holdSteady(Sequence $states): void
    {
        $this->spawn(1);
    }

    public function increase(Sequence $states): void
    {
        $running = $this->processes();

        if ($running->count() === 0) {
            $this->spawn(1);

            return;
        }

        $this->spawn($running->count());
    }

    public function dramaticIncrease(Sequence $states): void
    {
        $running = $this->processes();

        if ($running->count() === 0) {
            $this->spawn(2);

            return;
        }

        $this->spawn($running->count() * 2);
    }

    private function spawn(int $processes): void
    {
        for ($i = 0; $i < $processes; $i++) {
            $this->control->processes()->execute($this->spawn);
        }
    }

    /**
     * @return Map<int, Process>
     */
    private function processes(): Map
    {
        /** @psalm-suppress UnusedClosureParam */
        return $this
            ->status
            ->processes()
            ->all()
            ->filter(static function(int $pid, Process $process): bool {
                return Str::of($process->command()->toString())->contains(
                    'crawler consume crawler'
                );
            });
    }
}
