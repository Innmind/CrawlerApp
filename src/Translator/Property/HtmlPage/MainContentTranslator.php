<?php
declare(strict_types = 1);

namespace Crawler\Translator\Property\HtmlPage;

use Crawler\Translator\PropertyTranslator;
use Innmind\Crawler\HttpResource;
use Innmind\Rest\Client\Definition\Property;

final class MainContentTranslator implements PropertyTranslator
{
    public function supports(HttpResource $resource, Property $property): bool
    {
        return $resource->attributes()->contains('content');
    }

    public function translate(HttpResource $resource, Property $property)
    {
        return $resource
            ->attributes()
            ->get('content')
            ->content();
    }
}
