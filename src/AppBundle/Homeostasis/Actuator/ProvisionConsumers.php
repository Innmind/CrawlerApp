<?php
declare(strict_types = 1);

namespace AppBundle\Homeostasis\Actuator;

use Innmind\Homeostasis\Actuator;
use Innmind\Server\Status\{
    Server as Status,
    Server\Process
};
use Innmind\Server\Control\{
    Server as Control,
    Server\Command,
    Server\Signal,
    Server\Process\Pid
};
use Innmind\Immutable\{
    StreamInterface,
    MapInterface
};
use Psr\Log\LoggerInterface;

final class ProvisionConsumers implements Actuator
{
    private $status;
    private $control;
    private $logger;
    private $spawn;

    public function __construct(
        Status $status,
        Control $control,
        LoggerInterface $logger,
        string $workingDirectory,
        string $environment
    ) {
        $this->status = $status;
        $this->control = $control;
        $this->logger = $logger;
        $this->spawn = Command::background('php')
            ->withArgument('./console')
            ->withOption('env', $environment)
            ->withArgument('rabbitmq:consumer')
            ->withArgument('crawl')
            ->withShortOption('m', '50')
            ->withWorkingDirectory($workingDirectory);
    }

    /**
     * {@inheritdoc}
     */
    public function dramaticDecrease(StreamInterface $states): void
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

    /**
     * {@inheritdoc}
     */
    public function decrease(StreamInterface $states): void
    {
        //let the consumers finish by themselves
    }

    /**
     * {@inheritdoc}
     */
    public function holdSteady(StreamInterface $states): void
    {
        $this->spawn(1);
    }

    /**
     * {@inheritdoc}
     */
    public function increase(StreamInterface $states): void
    {
        $running = $this->processes();

        if ($running->count() === 0) {
            $this->spawn(1);

            return;
        }

        $this->spawn($running->count());
    }

    /**
     * {@inheritdoc}
     */
    public function dramaticIncrease(StreamInterface $states): void
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
        foreach (range(1, $processes) as $i) {
            $this->control->processes()->execute($this->spawn);
        }
    }

    /**
     * @return MapInterface<int, Process>
     */
    private function processes(): MapInterface
    {
        return $this
            ->status
            ->processes()
            ->all()
            ->filter(function(int $pid, Process $process): bool {
                return (string) $this->spawn === (string) $process->command();
            });
    }
}
