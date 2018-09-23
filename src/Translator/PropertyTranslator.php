<?php
declare(strict_types = 1);

namespace Crawler\Translator;

use Innmind\Crawler\HttpResource;
use Innmind\Rest\Client\Definition\Property;

interface PropertyTranslator
{
    public function supports(HttpResource $resource, Property $property): bool;

    /**
     * @return mixed
     */
    public function translate(HttpResource $resource, Property $property);
}
