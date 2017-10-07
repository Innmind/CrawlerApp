<?php
declare(strict_types = 1);

namespace AppBundle\Translator\Property;

use AppBundle\Translator\PropertyTranslator;
use Innmind\Crawler\HttpResource;
use Innmind\Rest\Client\Definition\Property;

final class CharsetTranslator implements PropertyTranslator
{
    public function supports(HttpResource $resource, Property $property): bool
    {
        return $resource->attributes()->contains('charset');
    }

    public function translate(HttpResource $resource, Property $property)
    {
        return (string) $resource
            ->attributes()
            ->get('charset')
            ->content();
    }
}
