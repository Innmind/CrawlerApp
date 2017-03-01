<?php
declare(strict_types = 1);

namespace AppBundle\Crawler;

use Innmind\Crawler\{
    CrawlerInterface,
    HttpResource
};
use Innmind\Xml\Reader\CacheReader;
use Innmind\Http\Message\RequestInterface;

final class XmlReaderAwareCrawler implements CrawlerInterface
{
    private $reader;
    private $crawler;

    public function __construct(
        CacheReader $reader,
        CrawlerInterface $crawler
    ) {
        $this->reader = $reader;
        $this->crawler = $crawler;
    }

    public function execute(RequestInterface $request): HttpResource
    {
        $resource = $this->crawler->execute($request);
        $this->reader->detach($resource->content());

        return $resource;
    }
}
