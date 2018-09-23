<?php
declare(strict_types = 1);

namespace Crawler\Publisher;

use Crawler\{
    Publisher as PublisherInterface,
    Reference,
    AMQP\Message\Link,
    SameUrlAs
};
use Innmind\Crawler\HttpResource;
use Innmind\Url\UrlInterface;
use Innmind\AMQP\Producer;

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
            $sameAs = new SameUrlAs($resource->url());
            $resource
                ->attributes()
                ->get('links')
                ->content()
                ->filter(function(UrlInterface $url) use ($sameAs): bool {
                    return !$sameAs($url);
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
