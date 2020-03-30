<?php
declare(strict_types = 1);

namespace Crawler;

use Innmind\Crawler\HttpResource;
use Innmind\Url\Url;

interface Publisher
{
    /**
     * @param HttpResource $resource The resource to crawl
     * @param Url $server The server where to publish the crawled resource
     */
    public function __invoke(
        HttpResource $resource,
        Url $server
    ): Reference;
}
