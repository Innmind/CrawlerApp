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
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;

final class LinksAwarePublisher implements PublisherInterface
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

        if ($resource->attributes()->contains('links')) {
            $resource
                ->attributes()
                ->get('links')
                ->content()
                ->filter(function(UrlInterface $url) use ($resource): bool {
                    return (string) $url !== (string) $resource->url();
                })
                ->foreach(function(UrlInterface $url) use ($reference, $resource): void {
                    $message = new Link(
                        $resource->url(),
                        $url,
                        $reference
                    );

                    $this->producer->publish((string) $message->body());
                });
        }

        return $reference;
    }
}
