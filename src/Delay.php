<?php
declare(strict_types = 1);

namespace Crawler;

use Innmind\TimeContinuum\{
    PeriodInterface,
    Period\Earth\Second,
    ElapsedPeriod,
};

final class Delay
{
    /**
     * Period by default to wait before hitting a server
     */
    public static function default(): PeriodInterface
    {
        return new Second(10);
    }

    /**
     * Threshold to trigger a forced delay before crawling a url
     */
    public static function threshold(): ElapsedPeriod
    {
        return new ElapsedPeriod(1000); // 1 second
    }

    /**
     * The minimum interval between 2 hits on the same server
     */
    public static function hitInterval(): ElapsedPeriod
    {
        return new ElapsedPeriod(5000); // 5 seconds
    }
}
