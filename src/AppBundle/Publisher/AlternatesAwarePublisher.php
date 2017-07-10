<?php
declare(strict_types = 1);

namespace AppBundle\Publisher;

use AppBundle\{
    PublisherInterface,
    Reference
};
use Innmind\Crawler\{
    HttpResource,
    HttpResource\Alternates,
    HttpResource\Alternate
};
use Innmind\Url\UrlInterface;
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

        if (
            $resource->attributes()->contains('alternates') &&
            $resource->attributes()->get('alternates') instanceof Alternates
        ) {
            $resource
                ->attributes()
                ->get('alternates')
                ->content()
                ->foreach(function(string $language, Alternate $alternate) use ($resource, $reference): void {
                    $alternate
                        ->content()
                        ->filter(function(UrlInterface $url) use ($resource): bool {
                            return (string) $url !== (string) $resource->url();
                        })
                        ->foreach(function(UrlInterface $url) use ($language, $reference): void {
                            $this->producer->publish(serialize([
                                'resource' => (string) $url,
                                'origin' => (string) $reference->identity(),
                                'relationship' => 'alternate',
                                'attributes' => [
                                    'language' => $language,
                                ],
                                'definition' => $reference->definition(),
                                'server' => (string) $reference->server(),
                            ]));
                        });
                });
        }

        return $reference;
    }
}
