<?php
declare(strict_types = 1);

namespace Crawler\Delayer;

use Crawler\{
    CrawlTracer,
    Delayer,
    Exception\HostNeverHit
};
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    ElapsedPeriod
};
use Innmind\Url\UrlInterface;

final class TracerAwareDelayer implements Delayer
{
    private $tracer;
    private $delay;
    private $clock;
    private $threshold;

    public function __construct(
        CrawlTracer $tracer,
        Delayer $delayer,
        TimeContinuumInterface $clock,
        int $threshold
    ) {
        $this->tracer = $tracer;
        $this->delay = $delayer;
        $this->clock = $clock;
        $this->threshold = new ElapsedPeriod($threshold);
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
