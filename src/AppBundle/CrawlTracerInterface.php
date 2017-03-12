<?php
declare(strict_types = 1);

namespace AppBundle;

use Innmind\Url\{
    UrlInterface,
    Authority\HostInterface
};
use Innmind\TimeContinuum\PointInTimeInterface;

interface CrawlTracerInterface
{
    public function trace(UrlInterface $url): self;
    public function isKnown(UrlInterface $url): bool;

    /**
     * @throws HostNeverHitException
     */
    public function lastHit(HostInterface $host): PointInTimeInterface;
}
