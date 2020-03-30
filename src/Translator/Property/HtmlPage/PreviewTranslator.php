<?php
declare(strict_types = 1);

namespace Crawler\Translator\Property\HtmlPage;

use Crawler\Translator\PropertyTranslator;
use Innmind\Crawler\HttpResource;
use Innmind\Rest\Client\Definition\Property;
use Innmind\Url\Url;
use Innmind\Immutable\Set;
use function Innmind\Immutable\first;

final class PreviewTranslator implements PropertyTranslator
{
    public function supports(HttpResource $resource, Property $property): bool
    {
        return $resource->attributes()->contains('preview');
    }

    public function translate(HttpResource $resource, Property $property)
    {
        /** @var Set<Url> */
        $previews = $resource
            ->attributes()
            ->get('preview')
            ->content();

        return first($previews)->toString();
    }
}
