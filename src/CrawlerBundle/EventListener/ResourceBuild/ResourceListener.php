<?php

namespace CrawlerBundle\EventListener\ResourceBuild;

use CrawlerBundle\Events;
use CrawlerBundle\Event\ResourcePropertyBuildEvent;
use Innmind\Rest\Client\HttpResource;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ResourceListener implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::RESOURCE_PROPERTY_BUILD => [
                ['injectPort', 0],
                ['injectQuery', 0],
            ]
        ];
    }

    /**
     * Inject the port property in the resource
     *
     * @param ResourcePropertyBuildEvent $event
     *
     * @return void
     */
    public function injectPort(ResourcePropertyBuildEvent $event)
    {
        $property = $event->getProperty();

        if ((string) $property !== 'port') {
            return;
        }

        $resource = $event->getResource();
        $port = 80;

        if ($resource->has('port')) {
            $port = $resource->get('port');
        }

        if ($port === null) {
            if ($resource->get('scheme') === 'https') {
                $port = 443;
            } else {
                $port = 80;
            }
        }

        $event->setValue($port);
    }

    /**
     * Inject the query property in the resource
     *
     * @param ResourcePropertyBuildEvent $event
     *
     * @return void
     */
    public function injectQuery(ResourcePropertyBuildEvent $event)
    {
        $property = $event->getProperty();

        if ((string) $property !== 'query') {
            return;
        }

        $event->setValue((string) $event->getResource()->get('query'));
    }
}
