<?php
declare(strict_types = 1);

namespace AppBundle\Factory;

use AppBundle\Translator\{
    Property\DelegationTranslator,
    PropertyTranslator
};
use Innmind\Immutable\Map;

final class PropertyDelegationTranslatorFactory
{
    public static function make(array $translators): DelegationTranslator
    {
        $map = new Map('string', PropertyTranslator::class);

        foreach ($translators as $key => $translator) {
            $map = $map->put($key, $translator);
        }

        return new DelegationTranslator($map);
    }
}
