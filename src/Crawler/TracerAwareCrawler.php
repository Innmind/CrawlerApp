<?php
declare(strict_types = 1);

namespace Crawler\Crawler;

use Crawler\{
    CrawlTracer,
    Exception\UrlCannotBeCrawled,
};
use Innmind\Crawler\{
    Crawler,
    HttpResource,
};
use Innmind\Http\Message\Request;

final class TracerAwareCrawler implements Crawler
{
    private CrawlTracer $tracer;
    private Crawler $crawl;

    public function __construct(
        CrawlTracer $tracer,
        Crawler $crawl
    ) {
        $this->tracer = $tracer;
        $this->crawl = $crawl;
    }

    public function __invoke(Request $request): HttpResource
    {
        if ($this->tracer->knows($request->url())) {
            throw new UrlCannotBeCrawled($request->url());
        }

        $resource = ($this->crawl)($request);
        $this->tracer->trace($request->url());
        $this->tracer->trace($resource->url());

        return $resource;
    }
}
