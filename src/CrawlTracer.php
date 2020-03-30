<?php
declare(strict_types = 1);

namespace Crawler;

use Innmind\Url\{
    Url,
    Authority\Host,
};
use Innmind\TimeContinuum\PointInTime;

interface CrawlTracer
{
    public function trace(Url $url): self;
    public function knows(Url $url): bool;

    /**
     * @throws HostNeverHitException
     */
    public function lastHit(Host $host): PointInTime;
}
