<?php
declare(strict_types = 1);

namespace Crawler\Delayer;

use Crawler\{
    Delayer,
    Delay,
};
use Innmind\OperatingSystem\CurrentProcess;
use Innmind\TimeContinuum\PeriodInterface;
use Innmind\Url\UrlInterface;

final class FixDelayer implements Delayer
{
    private $process;
    private $period;

    public function __construct(
        CurrentProcess $process,
        PeriodInterface $period = null
    ) {
        $this->process = $process;
        $this->period = $period ?? Delay::default();
    }

    public function __invoke(UrlInterface $url): void
    {
        $this->process->halt($this->period);
    }
}
