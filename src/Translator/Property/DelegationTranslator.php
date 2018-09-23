<?php
declare(strict_types = 1);

namespace Crawler\Translator\Property;

use Crawler\Translator\PropertyTranslator;
use Innmind\Crawler\HttpResource;
use Innmind\Rest\Client\Definition\Property;
use Innmind\Immutable\MapInterface;

final class DelegationTranslator implements PropertyTranslator
{
    private $translators;

    public function __construct(MapInterface $translators)
    {
        if (
            (string) $translators->keyType() !== 'string' ||
            (string) $translators->valueType() !== PropertyTranslator::class
        ) {
            throw new \TypeError(sprintf(
                'Argument 1 must be of type MapInterface<string, %s>',
                PropertyTranslator::class
            ));
        }

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
