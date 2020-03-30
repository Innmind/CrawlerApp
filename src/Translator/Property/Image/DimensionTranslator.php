<?php
declare(strict_types = 1);

namespace Crawler\Translator\Property\Image;

use Crawler\Translator\PropertyTranslator;
use Innmind\Crawler\{
    HttpResource,
    HttpResource\Attributes\Attributes,
};
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
        /** @var Map<string, Attributes> */
        $dimension = $resource
            ->attributes()
            ->get('dimension')
            ->content();

        /**
         * @psalm-suppress MixedMethodCall
         * @psalm-suppress InvalidArgument
         * @psalm-suppress MixedArgument
         */
        return Map::of('string', 'int')
            ('width', $dimension->get('width')->content())
            ('height', $dimension->get('height')->content());
    }
}
