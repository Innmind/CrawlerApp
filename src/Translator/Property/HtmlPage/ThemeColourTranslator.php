<?php
declare(strict_types = 1);

namespace Crawler\Translator\Property\HtmlPage;

use Crawler\Translator\PropertyTranslator;
use Innmind\Crawler\HttpResource;
use Innmind\Rest\Client\Definition\Property;

final class ThemeColourTranslator implements PropertyTranslator
{
    public function supports(HttpResource $resource, Property $property): bool
    {
        return $resource->attributes()->contains('theme-color');
    }

    public function translate(HttpResource $resource, Property $property)
    {
        /** @psalm-suppress MixedMethodCall */
        return $resource
            ->attributes()
            ->get('theme-color')
            ->content()
            ->toString();
    }
}
