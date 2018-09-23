<?php
declare(strict_types = 1);

namespace Crawler\Delayer;

use Crawler\{
    Delayer,
    Delay,
};
use Innmind\TimeWarp\Halt;
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    PeriodInterface,
};
use Innmind\Url\UrlInterface;

final class FixDelayer implements Delayer
{
    private $halt;
    private $clock;
    private $period;

    public function __construct(
        Halt $halt,
        TimeContinuumInterface $clock,
        PeriodInterface $period = null
    ) {
        $this->halt = $halt;
        $this->clock = $clock;
        $this->period = $period ?? Delay::default();
    }

    public function __invoke(UrlInterface $url): void
    {
        ($this->halt)($this->clock, $this->period);
    }
}
