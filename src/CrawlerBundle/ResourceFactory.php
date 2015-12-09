<?php

namespace CrawlerBundle;

use CrawlerBundle\Events;
use CrawlerBundle\Event\ResourcePropertyBuildEvent;
use CrawlerBundle\Event\ResourceBuildEvent;
use Innmind\Crawler\HttpResource as CrawledResource;
use Innmind\Rest\Client\HttpResource;
use Innmind\Rest\Client\Definition\ResourceDefinition;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ResourceFactory
{
    protected $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Transpose a crawled resource into another resource understable for the
     * given rest resource definition
     *
     * @param ResourceDefinition $definition
     * @param CrawledResource $crawled
     *
     * @return HttpResource
     */
    public function make(ResourceDefinition $definition, CrawledResource $crawled)
    {
        $resource = new HttpResource;

        foreach ($definition->getProperties() as $property) {
            if (
                $property->containsResource() ||
                !$crawled->has((string) $property) ||
                $property->getType() !== gettype($crawled->get((string) $property))
            ) {
                $this->dispatcher->dispatch(
                    Events::RESOURCE_PROPERTY_BUILD,
                    $event = new ResourcePropertyBuildEvent(
                        $definition,
                        $property,
                        $crawled
                    )
                );

                if ($event->hasValue()) {
                    $resource->set((string) $property, $event->getValue());
                }

                continue;
            }

            $resource->set((string) $property, $crawled->get((string) $property));
        }

        $this->dispatcher->dispatch(
            Events::RESOURCE_BUILD,
            new ResourceBuildEvent($resource, $definition, $crawled)
        );

        return $resource;
    }
}
