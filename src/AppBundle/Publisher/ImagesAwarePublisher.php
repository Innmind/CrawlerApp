<?php
declare(strict_types = 1);

namespace AppBundle\Publisher;

use AppBundle\{
    Publisher as PublisherInterface,
    Reference,
    AMQP\Message\Image
};
use Innmind\Crawler\HttpResource;
use Innmind\Url\UrlInterface;
use Innmind\AMQP\Producer;

final class ImagesAwarePublisher implements PublisherInterface
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

        if ($resource->attributes()->contains('images')) {
            $resource
                ->attributes()
                ->get('images')
                ->content()
                ->foreach(function(UrlInterface $image, string $description) use ($reference): void {
                    ($this->produce)(new Image(
                        $image,
                        $reference,
                        $description
                    ));
                });
        }

        return $reference;
    }
}
