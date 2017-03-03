<?php
declare(strict_types = 1);

namespace AppBundle\Translator\PropertyTranslator\HtmlPage;

use AppBundle\Translator\PropertyTranslatorInterface;
use Innmind\Crawler\HttpResource;
use Innmind\Rest\Client\Definition\Property;

final class AnchorsTranslator implements PropertyTranslatorInterface
{
    public function supports(HttpResource $resource, Property $property): bool
    {
        return $resource->attributes()->contains('anchors');
    }

    public function translate(HttpResource $resource, Property $property)
    {
        return $resource
            ->attributes()
            ->get('anchors')
            ->content();
    }
}
