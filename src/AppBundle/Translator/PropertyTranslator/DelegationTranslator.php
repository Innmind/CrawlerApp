<?php
declare(strict_types = 1);

namespace AppBundle\Translator\PropertyTranslator;

use AppBundle\{
    Translator\PropertyTranslatorInterface,
    Exception\InvalidArgumentException
};
use Innmind\Crawler\HttpResource;
use Innmind\Rest\Client\Definition\Property;
use Innmind\Immutable\MapInterface;

final class DelegationTranslator implements PropertyTranslatorInterface
{
    private $translators;

    public function __construct(MapInterface $translators)
    {
        if (
            (string) $translators->keyType() !== 'string' ||
            (string) $translators->valueType() !== PropertyTranslatorInterface::class
        ) {
            throw new InvalidArgumentException;
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
