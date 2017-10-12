<?php
declare(strict_types = 1);

namespace AppBundle\Publisher;

use AppBundle\{
    Publisher as PublisherInterface,
    Reference,
    AMQP\Message\Alternate as Message
};
use Innmind\Crawler\{
    HttpResource,
    HttpResource\Alternates,
    HttpResource\Alternate
};
use Innmind\Url\UrlInterface;
use Innmind\AMQPBundle\Producer;

final class AlternatesAwarePublisher implements PublisherInterface
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
                            ($this->produce)(new Message(
                                $url,
                                $reference,
                                $language
                            ));
                        });
                });
        }

        return $reference;
    }
}
