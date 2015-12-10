<?php

namespace CrawlerBundle\EventListener\ResourceBuild;

use CrawlerBundle\Events;
use CrawlerBundle\Event\ResourcePropertyBuildEvent;
use Innmind\Rest\Client\HttpResource;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ImagesListener implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::RESOURCE_PROPERTY_BUILD => 'injectImages',
        ];
    }

    /**
     * Inject images property in the resource
     *
     * @param ResourcePropertyBuildEvent $event
     *
     * @return void
     */
    public function injectImages(ResourcePropertyBuildEvent $event)
    {
        $property = $event->getProperty();

        if (
            (string) $property !== 'images' ||
            $property->getType() !== 'array' ||
            !$property->containsResource()
        ) {
            return;
        }

        $resource = $event->getResource();

        if (!$resource->has('images')) {
            return;
        }

        $definition = $property->getResource();

        if (
            !$definition->hasProperty('url') ||
            !$definition->hasProperty('description')
        ) {
            return;
        }

        $images = [];

        foreach ($resource->get('images') as $couple) {
            $image = new HttpResource;
            $image
                ->set('url', $couple[0])
                ->set('description', $couple[1]);
            $images[] = $image;
        }

        if (count($images) > 0) {
            $event->setValue($images);
        }
    }
}
