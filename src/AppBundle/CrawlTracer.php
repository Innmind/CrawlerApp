<?php
declare(strict_types = 1);

namespace AppBundle;

use Innmind\Url\{
    UrlInterface,
    Authority\HostInterface
};
use Innmind\TimeContinuum\PointInTimeInterface;

interface CrawlTracer
{
    public function trace(UrlInterface $url): self;
    public function knows(UrlInterface $url): bool;

    /**
     * @throws HostNeverHitException
     */
    public function lastHit(HostInterface $host): PointInTimeInterface;
}
