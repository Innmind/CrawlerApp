<?php
declare(strict_types = 1);

namespace Crawler;

use Innmind\Crawler\HttpResource;
use Innmind\Url\UrlInterface;

interface Publisher
{
    /**
     * @param HttpResource $resource The resource to crawl
     * @param UrlInterface $server The server where to publish the crawled resource
     */
    public function __invoke(
        HttpResource $resource,
        UrlInterface $server
    ): Reference;
}
