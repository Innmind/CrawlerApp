<?php
declare(strict_types = 1);

namespace AppBundle;

use Innmind\Url\UrlInterface;

interface PublisherInterface
{
    /**
     * @param UrlInterface $resource The resource to crawl
     * @param UrlInterface $server The server where to publish the crawled resource
     */
    public function __invoke(
        UrlInterface $resource,
        UrlInterface $server
    ): Reference;
}
