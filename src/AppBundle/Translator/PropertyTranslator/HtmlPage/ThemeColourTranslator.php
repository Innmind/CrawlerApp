<?php
declare(strict_types = 1);

namespace AppBundle\Translator\PropertyTranslator\HtmlPage;

use AppBundle\Translator\PropertyTranslatorInterface;
use Innmind\Crawler\HttpResource;
use Innmind\Rest\Client\Definition\Property;

final class ThemeColourTranslator implements PropertyTranslatorInterface
{
    public function supports(HttpResource $resource, Property $property): bool
    {
        return $resource->attributes()->contains('theme-color');
    }

    public function translate(HttpResource $resource, Property $property)
    {
        return (string) $resource
            ->attributes()
            ->get('theme-color')
            ->content();
    }
}
