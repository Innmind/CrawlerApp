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
    TimeContinuumInterface,
    ElapsedPeriodInterface,
};
use Innmind\Url\UrlInterface;

final class TracerAwareDelayer implements Delayer
{
    private CrawlTracer $tracer;
    private Delayer $delay;
    private TimeContinuumInterface $clock;
    private ElapsedPeriodInterface $threshold;

    public function __construct(
        CrawlTracer $tracer,
        Delayer $delayer,
        TimeContinuumInterface $clock,
        ElapsedPeriodInterface $threshold = null
    ) {
        $this->tracer = $tracer;
        $this->delay = $delayer;
        $this->clock = $clock;
        $this->threshold = $threshold ?? Delay::hitInterval();
    }

    public function __invoke(UrlInterface $url): void
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
