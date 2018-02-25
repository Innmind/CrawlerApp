<?php
declare(strict_types = 1);

namespace AppBundle\Publisher;

use AppBundle\{
    Publisher as PublisherInterface,
    Reference,
    AMQP\Message\Canonical
};
use Innmind\Crawler\HttpResource;
use Innmind\Url\UrlInterface;
use Innmind\AMQP\Producer;

final class CanonicalAwarePublisher implements PublisherInterface
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

        if (
            $resource->attributes()->contains('canonical') &&
            (string) $resource->attributes()->get('canonical')->content() !== (string) $resource->url()
        ) {
            ($this->produce)(new Canonical(
                $resource->attributes()->get('canonical')->content(),
                $reference
            ));
        }

        return $reference;
    }
}
