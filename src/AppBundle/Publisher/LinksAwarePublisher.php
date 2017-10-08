<?php
declare(strict_types = 1);

namespace AppBundle\Publisher;

use AppBundle\{
    Publisher as PublisherInterface,
    Reference,
    AMQP\Message\Link
};
use Innmind\Crawler\HttpResource;
use Innmind\Url\UrlInterface;
use Innmind\AMQPBundle\Producer;

final class LinksAwarePublisher implements PublisherInterface
{
    private $publisher;
    private $produce;

    public function __construct(
        PublisherInterface $publisher,
        Producer $producer
    ) {
        $this->publisher = $publisher;
        $this->produce = $producer;
    }

    public function __invoke(
        HttpResource $resource,
        UrlInterface $server
    ): Reference {
        $reference = ($this->publisher)($resource, $server);

        if ($resource->attributes()->contains('links')) {
            $resource
                ->attributes()
                ->get('links')
                ->content()
                ->filter(function(UrlInterface $url) use ($resource): bool {
                    return (string) $url !== (string) $resource->url();
                })
                ->foreach(function(UrlInterface $url) use ($reference, $resource): void {
                    ($this->produce)(new Link(
                        $resource->url(),
                        $url,
                        $reference
                    ));
                });
        }

        return $reference;
    }
}
