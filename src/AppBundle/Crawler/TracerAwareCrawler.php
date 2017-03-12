<?php
declare(strict_types = 1);

namespace AppBundle\Crawler;

use AppBundle\{
    CrawlTracerInterface,
    Exception\UrlCannotBeCrawledException
};
use Innmind\Crawler\{
    CrawlerInterface,
    HttpResource
};
use Innmind\Http\Message\RequestInterface;

final class TracerAwareCrawler implements CrawlerInterface
{
    private $tracer;
    private $crawler;

    public function __construct(
        CrawlTracerInterface $tracer,
        CrawlerInterface $crawler
    ) {
        $this->tracer = $tracer;
        $this->crawler = $crawler;
    }

    public function execute(RequestInterface $request): HttpResource
    {
        if ($this->tracer->isKnown($request->url())) {
            throw new UrlCannotBeCrawledException($request->url());
        }

        $resource = $this->crawler->execute($request);
        $this->tracer->trace($request->url());
        $this->tracer->trace($resource->url());

        return $resource;
    }
}
