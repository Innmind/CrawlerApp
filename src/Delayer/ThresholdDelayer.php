<?php
declare(strict_types = 1);

namespace Crawler\Delayer;

use Crawler\{
    Delayer,
    Delay,
};
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    ElapsedPeriodInterface,
};
use Innmind\Url\UrlInterface;

final class ThresholdDelayer implements Delayer
{
    private $attempt;
    private $fallback;
    private $clock;
    private $threshold;

    public function __construct(
        Delayer $attempt,
        Delayer $fallback,
        TimeContinuumInterface $clock,
        ElapsedPeriodInterface $threshold = null
    ) {
        $this->attempt = $attempt;
        $this->fallback = $fallback;
        $this->clock = $clock;
        $this->threshold = $threshold ?? Delay::threshold();
    }

    public function __invoke(UrlInterface $url): void
    {
        $start = $this->clock->now();
        ($this->attempt)($url);
        $waited = $this
            ->clock
            ->now()
            ->elapsedSince($start);

        if ($this->threshold->longerThan($waited)) {
            ($this->fallback)($url);
        }
    }
}
