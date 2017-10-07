<?php
declare(strict_types = 1);

namespace AppBundle\Crawler;

use Innmind\Crawler\{
    Crawler,
    HttpResource
};
use Innmind\Xml\Reader\CacheReader;
use Innmind\Http\Message\Request;

final class XmlReaderAwareCrawler implements Crawler
{
    private $reader;
    private $crawler;

    public function __construct(
        CacheReader $reader,
        Crawler $crawler
    ) {
        $this->reader = $reader;
        $this->crawler = $crawler;
    }

    public function execute(Request $request): HttpResource
    {
        $resource = $this->crawler->execute($request);
        $this->reader->detach($resource->content());

        return $resource;
    }
}
