<?php
declare(strict_types = 1);

namespace AppBundle\Publisher;

use AppBundle\{
    PublisherInterface,
    Reference,
    MediaType\Negotiator,
    MediaType\Pattern,
    Translator\HttpResourceTranslator,
    Exception\ResourceCannotBePublishedException,
    Exception\MediaTypeDoesntMatchAnyException
};
use Innmind\Rest\Client\{
    Client,
    Definition\HttpResource as Definition
};
use Innmind\Crawler\HttpResource;
use Innmind\Url\UrlInterface;
use Innmind\Immutable\Set;

final class Publisher implements PublisherInterface
{
    private $client;
    private $translator;
    private $negotiator;

    public function __construct(
        Client $client,
        HttpResourceTranslator $translator
    ) {
        $this->client = $client;
        $this->translator = $translator;
        $this->negotiator = new Negotiator;
    }

    public function __invoke(
        HttpResource $resource,
        UrlInterface $server
    ): Reference {
        $server = $this->client->server((string) $server);
        $definitions = $server
            ->capabilities()
            ->definitions()
            ->filter(function(string $name, Definition $definition): bool {
                return $definition->metas()->contains('allowed_media_types') &&
                    is_array($definition->metas()->get('allowed_media_types'));
            });

        if ($definitions->size() === 0) {
            throw new ResourceCannotBePublishedException($resource);
        }

        $mediaTypes = $definitions
            ->reduce(
                new Set('string'),
                function(Set $carry, string $name, Definition $definition): Set {
                    foreach ($definition->metas()->get('allowed_media_types') as $value) {
                        $carry = $carry->add($value);
                    }

                    return $carry;
                }
            )
            ->reduce(
                new Set(Pattern::class),
                function(Set $carry, string $mediaType): Set {
                    return $carry->add(Pattern::fromString($mediaType));
                }
            );

        try {
            $best = $this->negotiator->best(
                $resource->mediaType(),
                $mediaTypes
            );
        } catch (MediaTypeDoesntMatchAnyException $e) {
            throw new ResourceCannotBePublishedException($resource);
        }

        $definition = $definitions
            ->filter(function(string $name, Definition $definition) use ($best): bool {
                return in_array(
                    (string) $best,
                    $definition->metas()->get('allowed_media_types')
                );
            })
            ->current();

        return new Reference(
            $server->create(
                $this->translator->translate($resource, $definition)
            ),
            $definition->name(),
            $server->url()
        );
    }
}
