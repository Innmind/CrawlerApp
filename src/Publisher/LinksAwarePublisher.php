<?php
declare(strict_types = 1);

namespace Crawler\Publisher;

use Crawler\{
    Publisher as PublisherInterface,
    Reference,
    AMQP\Message\Link,
    SameUrlAs,
};
use Innmind\Crawler\HttpResource;
use Innmind\Url\Url;
use Innmind\AMQP\Producer;
use Innmind\Immutable\Set;

final class LinksAwarePublisher implements PublisherInterface
{
    private PublisherInterface $publisher;
    private Producer $produce;

    public function __construct(
        PublisherInterface $publisher,
        Producer $producer
    ) {
        $this->publisher = $publisher;
        $this->produce = $producer;
    }

    public function __invoke(
        HttpResource $resource,
        Url $server
    ): Reference {
        $reference = ($this->publisher)($resource, $server);

        if ($resource->attributes()->contains('links')) {
            $sameAs = new SameUrlAs($resource->url());
            /** @var Set<Url> */
            $links = $resource
                ->attributes()
                ->get('links')
                ->content();
            $links
                ->filter(static function(Url $url) use ($sameAs): bool {
                    return !$sameAs($url);
                })
                ->foreach(function(Url $url) use ($reference, $resource): void {
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
