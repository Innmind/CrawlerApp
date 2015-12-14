<?php

namespace CrawlerBundle\EventListener\ResourceBuild;

use CrawlerBundle\Events;
use CrawlerBundle\Event\ResourceBuildEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LinkRemoverListener implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::RESOURCE_BUILD => 'removeOwnLink',
        ];
    }

    /**
     * Remove the resource own url from the links it points to
     *
     * @param ResourceBuildEvent $event
     *
     * @return void
     */
    public function removeOwnLink(ResourceBuildEvent $event)
    {
        $current = $event->getCrawledResource()->getUrl();
        $resource = $event->getRestResource();

        if (!$resource->has('links')) {
            return;
        }

        $links = array_filter(
            $resource->get('links'),
            function ($url) use ($current) {
                return $url !== $current;
            }
        );
        $resource->set('links', array_values($links));
    }
}
