<?php
declare(strict_types = 1);

namespace Crawler\MediaType;

use Crawler\Exception\MediaTypeDoesntMatchAny;
use Innmind\Filesystem\MediaType;
use Innmind\Immutable\SetInterface;

final class Negotiator
{
    /**
     * @param SetInterface<Pattern> $patterns
     */
    public function best(MediaType $mediaType, SetInterface $pattterns): Pattern
    {
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
