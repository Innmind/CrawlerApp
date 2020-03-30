<?php
declare(strict_types = 1);

namespace Crawler\Delayer;

use Crawler\{
    Delayer,
    Delay,
};
use Innmind\OperatingSystem\CurrentProcess;
use Innmind\TimeContinuum\Period;
use Innmind\Url\Url;

final class FixDelayer implements Delayer
{
    private CurrentProcess $process;
    private Period $period;

    public function __construct(
        CurrentProcess $process,
        Period $period = null
    ) {
        $this->process = $process;
        $this->period = $period ?? Delay::default();
    }

    public function __invoke(Url $url): void
    {
        $this->process->halt($this->period);
    }
}
