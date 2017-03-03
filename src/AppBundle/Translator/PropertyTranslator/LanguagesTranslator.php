<?php
declare(strict_types = 1);

namespace AppBundle\Translator\PropertyTranslator;

use AppBundle\Translator\PropertyTranslatorInterface;
use Innmind\Crawler\HttpResource;
use Innmind\Rest\Client\Definition\Property;

final class LanguagesTranslator implements PropertyTranslatorInterface
{
    public function supports(HttpResource $resource, Property $property): bool
    {
        return $resource->attributes()->contains('languages');
    }

    public function translate(HttpResource $resource, Property $property)
    {
        return $resource
            ->attributes()
            ->get('languages')
            ->content();
    }
}
