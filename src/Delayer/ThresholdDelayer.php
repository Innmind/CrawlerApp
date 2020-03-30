<?php
declare(strict_types = 1);

namespace Crawler\Delayer;

use Crawler\{
    Delayer,
    Delay,
};
use Innmind\TimeContinuum\{
    Clock,
    ElapsedPeriod,
};
use Innmind\Url\Url;

final class ThresholdDelayer implements Delayer
{
    private Delayer $attempt;
    private Delayer $fallback;
    private Clock $clock;
    private ElapsedPeriod $threshold;

    public function __construct(
        Delayer $attempt,
        Delayer $fallback,
        Clock $clock,
        ElapsedPeriod $threshold = null
    ) {
        $this->attempt = $attempt;
        $this->fallback = $fallback;
        $this->clock = $clock;
        $this->threshold = $threshold ?? Delay::threshold();
    }

    public function __invoke(Url $url): void
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
