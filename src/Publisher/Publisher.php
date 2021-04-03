<?php
declare(strict_types = 1);

namespace Crawler\Publisher;

use Crawler\{
    Publisher as PublisherInterface,
    Reference,
    MediaType\Negotiator,
    MediaType\Pattern,
    Translator\HttpResourceTranslator,
    Exception\ResourceCannotBePublished,
    Exception\MediaTypeDoesntMatchAny,
};
use Innmind\Rest\Client\{
    Client,
    Definition\HttpResource as Definition,
};
use Innmind\Crawler\HttpResource;
use Innmind\Url\Url;
use Innmind\Immutable\Set;

final class Publisher implements PublisherInterface
{
    private Client $client;
    private HttpResourceTranslator $translator;
    private Negotiator $negotiator;

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
        Url $server
    ): Reference {
        $server = $this->client->server($server->toString());
        /** @psalm-suppress UnusedClosureParam */
        $definitions = $server
            ->capabilities()
            ->definitions()
            ->filter(static function(string $name, Definition $definition): bool {
                return $definition->metas()->contains('allowed_media_types') &&
                    \is_array($definition->metas()->get('allowed_media_types'));
            });

        /**
         * @psalm-suppress UnusedClosureParam
         * @var Set<Pattern>
         */
        $mediaTypes = $definitions
            ->reduce(
                Set::of('string'),
                static function(Set $carry, string $name, Definition $definition): Set {
                    /**
                     * @psalm-suppress PossiblyInvalidIterator
                     * @var string $value
                     */
                    foreach ($definition->metas()->get('allowed_media_types') as $value) {
                        $carry = $carry->add($value);
                    }

                    return $carry;
                }
            )
            ->reduce(
                Set::of(Pattern::class),
                static function(Set $carry, string $mediaType): Set {
                    /** @psalm-suppress MixedArgument */
                    return $carry->add(Pattern::of($mediaType));
                }
            );

        try {
            $best = $this->negotiator->best(
                $resource->mediaType(),
                $mediaTypes
            );
        } catch (MediaTypeDoesntMatchAny $e) {
            throw new ResourceCannotBePublished($resource);
        }

        $definition = $definitions
            ->values()
            ->filter(static function(Definition $definition) use ($best): bool {
                /** @psalm-suppress PossiblyInvalidArgument */
                return \in_array(
                    $best->toString(),
                    $definition->metas()->get('allowed_media_types'),
                    true,
                );
            })
            ->first();

        return new Reference(
            $server->create(
                $this->translator->translate($resource, $definition)
            ),
            $definition->name(),
            $server->url()
        );
    }
}
