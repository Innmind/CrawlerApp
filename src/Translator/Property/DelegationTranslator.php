<?php
declare(strict_types = 1);

namespace Crawler\Translator\Property;

use Crawler\Translator\PropertyTranslator;
use Innmind\Crawler\HttpResource;
use Innmind\Rest\Client\Definition\Property;
use Innmind\Immutable\Map;
use function Innmind\Immutable\assertMap;

final class DelegationTranslator implements PropertyTranslator
{
    /** @var Map<string, PropertyTranslator> */
    private Map $translators;

    /**
     * @param Map<string, PropertyTranslator> $translators
     */
    public function __construct(Map $translators)
    {
        assertMap('string', PropertyTranslator::class, $translators, 1);

        $this->translators = $translators;
    }

    public function supports(HttpResource $resource, Property $property): bool
    {
        return $this->translators->contains($property->name()) &&
            $this
                ->translators
                ->get($property->name())
                ->supports($resource, $property);
    }

    public function translate(HttpResource $resource, Property $property)
    {
        return $this
            ->translators
            ->get($property->name())
            ->translate($resource, $property);
    }
}
