<?php
declare(strict_types = 1);

namespace Crawler\Translator\Property;

use Crawler\Translator\PropertyTranslator;
use Innmind\Crawler\HttpResource;
use Innmind\Rest\Client\Definition\Property;

final class HostTranslator implements PropertyTranslator
{
    public function supports(HttpResource $resource, Property $property): bool
    {
        return true;
    }

    public function translate(HttpResource $resource, Property $property)
    {
        return $resource
            ->url()
            ->authority()
            ->host()
            ->toString();
    }
}
