<?php
declare(strict_types = 1);

namespace Crawler\Translator\Property\HtmlPage;

use Crawler\Translator\PropertyTranslator;
use Innmind\Crawler\HttpResource;
use Innmind\Rest\Client\Definition\Property;

final class DescriptionTranslator implements PropertyTranslator
{
    public function supports(HttpResource $resource, Property $property): bool
    {
        return $resource->attributes()->contains('description');
    }

    public function translate(HttpResource $resource, Property $property)
    {
        return $resource
            ->attributes()
            ->get('description')
            ->content();
    }
}
