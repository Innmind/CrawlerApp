<?php
declare(strict_types = 1);

namespace Crawler\Publisher;

use Crawler\{
    Publisher as PublisherInterface,
    Reference,
    AMQP\Message\Image,
};
use Innmind\Crawler\HttpResource;
use Innmind\Url\Url;
use Innmind\AMQP\Producer;
use Innmind\Immutable\Map;

final class ImagesAwarePublisher implements PublisherInterface
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

        if ($resource->attributes()->contains('images')) {
            /** @var Map<Url, string> */
            $images = $resource
                ->attributes()
                ->get('images')
                ->content();
            $images->foreach(function(Url $image, string $description) use ($reference): void {
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
