<?php
declare(strict_types = 1);

namespace AppBundle\Publisher;

use AppBundle\{
    PublisherInterface,
    Reference
};
use Innmind\Crawler\HttpResource;
use Innmind\Url\UrlInterface;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;

final class CanonicalAwarePublisher implements PublisherInterface
{
    private $publisher;
    private $producer;

    public function __construct(
        PublisherInterface $publisher,
        ProducerInterface $producer
    ) {
        $this->publisher = $publisher;
        $this->producer = $producer;
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
            $this->producer->publish(serialize([
                'resource' => (string) $resource->attributes()->get('canonical')->content(),
                'canonical_of' => (string) $resource->url(),
                'definition' => $reference->definition()->name(),
                'server' => (string) $server,
            ]));
        }

        return $reference;
    }
}
