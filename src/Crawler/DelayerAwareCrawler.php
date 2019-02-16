<?php
declare(strict_types = 1);

namespace Crawler\Crawler;

use Crawler\Delayer;
use Innmind\Crawler\{
    Crawler,
    HttpResource
};
use Innmind\Http\Message\Request;

final class DelayerAwareCrawler implements Crawler
{
    private $delay;
    private $crawl;

    public function __construct(
        Delayer $delayer,
        Crawler $crawl
    ) {
        $this->delay = $delayer;
        $this->crawl = $crawl;
    }

    public function __invoke(Request $request): HttpResource
    {
        ($this->delay)($request->url());

        return ($this->crawl)($request);
    }
}
