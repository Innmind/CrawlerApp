<?php
declare(strict_types = 1);

namespace Crawler\Crawler;

use Innmind\Crawler\{
    Crawler,
    HttpResource
};
use Innmind\Xml\Reader\Cache\Storage;
use Innmind\Http\Message\Request;

final class XmlReaderAwareCrawler implements Crawler
{
    private $storage;
    private $crawl;

    public function __construct(
        Storage $storage,
        Crawler $crawl
    ) {
        $this->storage = $storage;
        $this->crawl = $crawl;
    }

    public function __invoke(Request $request): HttpResource
    {
        $resource = ($this->crawl)($request);
        $this->storage->remove($resource->content());

        return $resource;
    }
}
