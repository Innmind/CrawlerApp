<?php
declare(strict_types = 1);

namespace Crawler\Publisher;

use Crawler\{
    Publisher as PublisherInterface,
    Reference,
    AMQP\Message\Canonical,
};
use Innmind\Crawler\HttpResource;
use Innmind\Url\Url;
use Innmind\AMQP\Producer;

final class CanonicalAwarePublisher implements PublisherInterface
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

        if (
            $resource->attributes()->contains('canonical') &&
            $resource->attributes()->get('canonical')->content()->toString() !== $resource->url()->toString()
        ) {
            ($this->produce)(new Canonical(
                $resource->attributes()->get('canonical')->content(),
                $reference
            ));
        }

        return $reference;
    }
}
