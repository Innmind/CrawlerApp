<?php
declare(strict_types = 1);

namespace AppBundle\Publisher;

use AppBundle\{
    PublisherInterface,
    Reference
};
use Innmind\Crawler\HttpResource;
use Innmind\Url\UrlInterface;
use Innmind\Immutable\SetInterface;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;

final class AlternatesAwarePublisher implements PublisherInterface
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

        if ($resource->attributes()->contains('alternates')) {
            $resource
                ->attributes()
                ->get('alternates')
                ->content()
                ->foreach(function(string $language, SetInterface $urls) use ($resource, $reference, $server): void {
                    $urls
                        ->filter(function(UrlInterface $url) use ($resource): bool {
                            return (string) $url !== (string) $resource->url();
                        })
                        ->foreach(function(UrlInterface $url) use ($language, $reference, $server): void {
                            $this->producer->publish(serialize([
                                'resource' => (string) $url,
                                'origin' => (string) $reference->identity(),
                                'relationship' => 'alternate',
                                'attributes' => [
                                    'language' => $language,
                                ],
                                'definition' => $reference->definition()->name(),
                                'server' => (string) $server,
                            ]));
                        });
                });
        }

        return $reference;
    }
}
