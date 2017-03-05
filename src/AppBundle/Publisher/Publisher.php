<?php
declare(strict_types = 1);

namespace AppBundle\Publisher;

use AppBundle\{
    PublisherInterface,
    Reference,
    Translator\HttpResourceTranslator,
    Exception\ResourceCannotBePublishedException
};
use Innmind\Rest\Client\{
    ClientInterface,
    Definition\HttpResource as Definition
};
use Innmind\Crawler\HttpResource;
use Innmind\Url\UrlInterface;
use Innmind\Http\{
    Message\Request,
    Message\Method,
    ProtocolVersion
};
use Innmind\Filesystem\MediaType\MediaType;
use Innmind\Immutable\Map;
use Negotiation\{
    Negotiator,
    Accept
};

final class Publisher implements PublisherInterface
{
    private $client;
    private $translator;
    private $negotiator;

    public function __construct(
        ClientInterface $client,
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

        $mediaTypes = $definitions->reduce(
            [],
            function(array $carry, string $name, Definition $definition): array {
                return array_merge(
                    $carry,
                    $definition->metas()->get('allowed_media_types')
                );
            }
        );
        $mediaType = new MediaType(
            $resource->mediaType()->topLevel(),
            $resource->mediaType()->subType()
        );
        $best = $this->negotiator->getBest(
            (string) $mediaType,
            $mediaTypes
        );

        if (!$best instanceof Accept) {
            throw new ResourceCannotBePublishedException($resource);
        }

        $definition = $definitions
            ->filter(function(string $name, Definition $definition) use ($best): bool {
                return in_array(
                    $best->getValue(),
                    $definition->metas()->get('allowed_media_types')
                );
            })
            ->current();

        return new Reference(
            $server->create(
                $this->translator->translate($resource, $definition)
            ),
            $definition
        );
    }
}
