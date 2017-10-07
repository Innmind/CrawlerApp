<?php
declare(strict_types = 1);

namespace AppBundle\Translator\Property\HtmlPage;

use AppBundle\Translator\PropertyTranslator;
use Innmind\Crawler\HttpResource;
use Innmind\Rest\Client\Definition\Property;

final class TitleTranslator implements PropertyTranslator
{
    public function supports(HttpResource $resource, Property $property): bool
    {
        return $resource->attributes()->contains('title');
    }

    public function translate(HttpResource $resource, Property $property)
    {
        return $resource
            ->attributes()
            ->get('title')
            ->content();
    }
}
