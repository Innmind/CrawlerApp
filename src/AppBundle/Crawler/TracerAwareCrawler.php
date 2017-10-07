<?php
declare(strict_types = 1);

namespace AppBundle\Crawler;

use AppBundle\{
    CrawlTracer,
    Exception\UrlCannotBeCrawled
};
use Innmind\Crawler\{
    Crawler,
    HttpResource
};
use Innmind\Http\Message\Request;

final class TracerAwareCrawler implements Crawler
{
    private $tracer;
    private $crawler;

    public function __construct(
        CrawlTracer $tracer,
        Crawler $crawler
    ) {
        $this->tracer = $tracer;
        $this->crawler = $crawler;
    }

    public function execute(Request $request): HttpResource
    {
        if ($this->tracer->knows($request->url())) {
            throw new UrlCannotBeCrawled($request->url());
        }

        $resource = $this->crawler->execute($request);
        $this->tracer->trace($request->url());
        $this->tracer->trace($resource->url());

        return $resource;
    }
}
