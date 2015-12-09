<?php

namespace CrawlerBundle\Event;

use Innmind\Crawler\HttpResource as CrawledResource;
use Innmind\Rest\Client\HttpResource;
use Innmind\Rest\Client\Definition\ResourceDefinition;
use Symfony\Component\EventDispatcher\Event;

class ResourceBuildEvent extends Event
{
    protected $restResource;
    protected $definition;
    protected $crawledResource;

    public function __construct(
        HttpResource $restResource,
        ResourceDefinition $definition,
        CrawledResource $crawledResource
    ) {
        $this->restResource = $restResource;
        $this->definition = $definition;
        $this->crawledResource = $crawledResource;
    }

    /**
     * Return the rest resource that has been built
     *
     * @return HttpResource
     */
    public function getRestResource()
    {
        return $this->restResource;
    }

    /**
     * Return the definition of the rest resource
     *
     * @return ResourceDefinition
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * Return the crawled resource (origin of the data put in the rest resource)
     *
     * @return CrawledResource
     */
    public function getCrawledResource()
    {
        return $this->crawledResource;
    }
}
