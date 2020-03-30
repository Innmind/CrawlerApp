<?php
declare(strict_types = 1);

namespace Crawler;

use Crawler\Exception\HostNeverHit;
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
     * @throws HostNeverHit
     */
    public function lastHit(Host $host): PointInTime;
}
