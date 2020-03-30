<?php
declare(strict_types = 1);

namespace Crawler\Publisher;

use Crawler\{
    Publisher as PublisherInterface,
    Reference,
    AMQP\Message\Alternate as Message,
    SameUrlAs,
};
use Innmind\Crawler\{
    HttpResource,
    HttpResource\Alternates,
    HttpResource\Alternate,
};
use Innmind\Url\Url;
use Innmind\AMQP\Producer;

final class AlternatesAwarePublisher implements PublisherInterface
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
            $resource->attributes()->contains('alternates') &&
            $resource->attributes()->get('alternates') instanceof Alternates
        ) {
            $sameAs = new SameUrlAs($resource->url());
            $resource
                ->attributes()
                ->get('alternates')
                ->content()
                ->foreach(function(string $language, Alternate $alternate) use ($sameAs, $reference): void {
                    $alternate
                        ->content()
                        ->filter(function(Url $url) use ($sameAs): bool {
                            return !$sameAs($url);
                        })
                        ->foreach(function(Url $url) use ($language, $reference): void {
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
