<?php
declare(strict_types = 1);

namespace Crawler\MediaType;

use Crawler\Exception\MediaTypeDoesntMatchAny;
use Innmind\MediaType\MediaType;
use Innmind\Immutable\{
    Set,
    Sequence,
};

final class Negotiator
{
    /**
     * @param Set<Pattern> $patterns
     */
    public function best(MediaType $mediaType, Set $pattterns): Pattern
    {
        /** @var Sequence<Pattern> */
        $pattterns = $pattterns
            ->filter(function(Pattern $pattern) use ($mediaType): bool {
                return $pattern->matches($mediaType);
            })
            ->sort(function(Pattern $a, Pattern $b): int {
                return $b->quality() <=> $a->quality();
            });

        if ($pattterns->size() === 0) {
            throw new MediaTypeDoesntMatchAny;
        }

        return $pattterns->first();
    }
}
