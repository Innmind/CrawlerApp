<?php

namespace CrawlerBundle\EventListener\ResourceBuild;

use CrawlerBundle\Events;
use CrawlerBundle\Event\ResourcePropertyBuildEvent;
use Innmind\Rest\Client\HttpResource;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AlternatesListener implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::RESOURCE_PROPERTY_BUILD => 'injectAlternates',
        ];
    }

    /**
     * Inject alternates property in the resource
     *
     * @param ResourcePropertyBuildEvent $event
     *
     * @return void
     */
    public function injectAlternates(ResourcePropertyBuildEvent $event)
    {
        $property = $event->getProperty();

        if (
            (string) $property !== 'alternates' ||
            $property->getType() !== 'array' ||
            !$property->containsResource()
        ) {
            return;
        }

        $resource = $event->getResource();

        if (!$resource->has('alternates')) {
            return;
        }

        $definition = $property->getResource();

        if (
            !$definition->hasProperty('url') ||
            !$definition->hasProperty('language')
        ) {
            return;
        }

        $alternates = [];

        foreach ($resource->get('alternates') as $lang => $links) {
            foreach ($links as $link) {
                $alternate = new HttpResource;
                $alternate
                    ->set('url', $link)
                    ->set('language', $lang);
                $alternates[] = $alternate;
            }
        }

        if (count($alternates) > 0) {
            $event->setValue($alternates);
        }
    }
}
