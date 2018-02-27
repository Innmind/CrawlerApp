<?php
declare(strict_types = 1);

namespace Crawler\Translator\Property\Image;

use Crawler\Translator\PropertyTranslator;
use Innmind\Crawler\HttpResource;
use Innmind\Rest\Client\Definition\Property;
use Innmind\Immutable\Map;

final class DimensionTranslator implements PropertyTranslator
{
    public function supports(HttpResource $resource, Property $property): bool
    {
        return $resource->attributes()->contains('dimension');
    }

    public function translate(HttpResource $resource, Property $property)
    {
        $dimension = $resource
            ->attributes()
            ->get('dimension')
            ->content();

        return (new Map('string', 'int'))
            ->put('width', $dimension->get('width')->content())
            ->put('height', $dimension->get('height')->content());
    }
}