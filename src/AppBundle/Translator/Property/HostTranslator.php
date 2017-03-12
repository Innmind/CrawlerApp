<?php
declare(strict_types = 1);

namespace AppBundle\Translator\Property;

use AppBundle\Translator\PropertyTranslatorInterface;
use Innmind\Crawler\HttpResource;
use Innmind\Rest\Client\Definition\Property;

final class HostTranslator implements PropertyTranslatorInterface
{
    public function supports(HttpResource $resource, Property $property): bool
    {
        return true;
    }

    public function translate(HttpResource $resource, Property $property)
    {
        return (string) $resource
            ->url()
            ->authority()
            ->host();
    }
}
