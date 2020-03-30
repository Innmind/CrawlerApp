<?php
declare(strict_types = 1);

namespace Crawler\Delayer;

use Crawler\{
    CrawlTracer,
    Delayer,
    Delay,
    Exception\HostNeverHit,
};
use Innmind\TimeContinuum\{
    Clock,
    ElapsedPeriod,
};
use Innmind\Url\Url;

final class TracerAwareDelayer implements Delayer
{
    private CrawlTracer $tracer;
    private Delayer $delay;
    private Clock $clock;
    private ElapsedPeriod $threshold;

    public function __construct(
        CrawlTracer $tracer,
        Delayer $delayer,
        Clock $clock,
        ElapsedPeriod $threshold = null
    ) {
        $this->tracer = $tracer;
        $this->delay = $delayer;
        $this->clock = $clock;
        $this->threshold = $threshold ?? Delay::hitInterval();
    }

    public function __invoke(Url $url): void
    {
        try {
            $lastHit = $this->tracer->lastHit($url->authority()->host());
            $delta = $this
                ->clock
                ->now()
                ->elapsedSince($lastHit);

            if ($this->threshold->longerThan($delta)) {
                ($this->delay)($url);
            }
        } catch (HostNeverHit $e) {
            //pass
        }
    }
}
