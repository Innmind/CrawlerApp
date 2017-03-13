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

final class ImagesAwarePublisher implements PublisherInterface
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

        if ($resource->attributes()->contains('images')) {
            $resource
                ->attributes()
                ->get('images')
                ->content()
                ->foreach(function(UrlInterface $image, string $description) use ($reference, $server): void {
                    $this->producer->publish(serialize([
                        'resource' => (string) $image,
                        'referenced_in' => (string) $reference->identity(),
                        'description' => $description,
                        'definition' => $reference->definition()->name(),
                        'server' => (string) $server,
                    ]));
                });
        }

        return $reference;
    }
}
