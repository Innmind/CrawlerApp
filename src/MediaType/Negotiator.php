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
    public function best(MediaType $mediaType, Set $patterns): Pattern
    {
        /** @var Sequence<Pattern> */
        $patterns = $patterns
            ->filter(function(Pattern $pattern) use ($mediaType): bool {
                return $pattern->matches($mediaType);
            })
            ->sort(function(Pattern $a, Pattern $b): int {
                return $b->quality() <=> $a->quality();
            });

        if ($patterns->empty()) {
            throw new MediaTypeDoesntMatchAny;
        }

        return $patterns->first();
    }
}
