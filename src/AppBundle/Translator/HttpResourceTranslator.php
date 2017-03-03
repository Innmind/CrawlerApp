<?php
declare(strict_types = 1);

namespace AppBundle\Translator;

use Innmind\Crawler\HttpResource as CrawledResource;
use Innmind\Rest\Client\{
    Definition\HttpResource as Definition,
    Definition\Property as PropertyDefinition,
    HttpResource,
    HttpResource\Property
};
use Innmind\Immutable\Map;

final class HttpResourceTranslator
{
    private $translator;

    public function __construct(PropertyTranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function translate(
        CrawledResource $resource,
        Definition $definition
    ): HttpResource {
        $properties = $definition
            ->properties()
            ->filter(function(string $name, PropertyDefinition $definition) use ($resource): bool {
                return $this->translator->supports($resource, $definition);
            })
            ->reduce(
                new Map('string', Property::class),
                function(Map $carry, string $name, PropertyDefinition $definition) use ($resource): Map {
                    return $carry->put(
                        $name,
                        new Property(
                            $name,
                            $this->translator->translate(
                                $resource,
                                $definition
                            )
                        )
                    );
                }
            );

        return new HttpResource($definition->name(), $properties);
    }
}
