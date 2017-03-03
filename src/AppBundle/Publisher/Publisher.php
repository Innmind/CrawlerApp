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
    Definition\HttpResource as Definition,
    HttpResource,
    HttpResource\Property
};
use Innmind\Crawler\CrawlerInterface;
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
    private $crawler;
    private $translator;
    private $negotiator;

    public function __construct(
        ClientInterface $client,
        CrawlerInterface $crawler,
        HttpResourceTranslator $translator
    ) {
        $this->client = $client;
        $this->crawler = $crawler;
        $this->translator = $translator;
        $this->negotiator = new Negotiator;
    }

    public function __invoke(
        UrlInterface $resource,
        UrlInterface $server
    ): Reference {
        $crawledResource = $this->crawler->execute(
            new Request(
                $resource,
                new Method(Method::GET),
                new ProtocolVersion(1, 1)
            )
        );
        $server = $this->client->server((string) $server);
        $definitions = $server
            ->capabilities()
            ->definitions()
            ->filter(function(string $name, Definition $definition): bool {
                return $definition->metas()->contains('allowed_media_types') &&
                    is_array($definition->metas()->get('allowed_media_types'));
            });

        if ($definitions->size() === 0) {
            throw new ResourceCannotBePublishedException($crawledResource);
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
            $crawledResource->mediaType()->topLevel(),
            $crawledResource->mediaType()->subType()
        );
        $best = $this->negotiator->getBest(
            (string) $mediaType,
            $mediaTypes
        );

        if (!$best instanceof Accept) {
            throw new ResourceCannotBePublishedException($crawledResource);
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
                $this->translator->translate($crawledResource, $definition)
            ),
            $definition
        );
    }
}
