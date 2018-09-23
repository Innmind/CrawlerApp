<?php
declare(strict_types = 1);

namespace Crawler\Translator\Property;

use Crawler\Translator\PropertyTranslator;
use Innmind\Crawler\HttpResource;
use Innmind\Rest\Client\Definition\Property;

final class QueryTranslator implements PropertyTranslator
{
    public function supports(HttpResource $resource, Property $property): bool
    {
        return true;
    }

    public function translate(HttpResource $resource, Property $property)
    {
        return (string) $resource
            ->url()
            ->query();
    }
}
