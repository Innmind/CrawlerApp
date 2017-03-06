<?php
declare(strict_types = 1);

namespace AppBundle\MediaType;

use AppBundle\Exception\MediaTypeDoesntMatchAnyException;
use Innmind\Immutable\SetInterface;

final class Negotiator
{
    /**
     * @param SetInterface<Pattern> $available
     */
    public function best(Pattern $mediaType, SetInterface $available): Pattern
    {
        $available = $available
            ->filter(function(Pattern $possibility) use ($mediaType): bool {
                if (
                    $possibility->topLevel() === '*' &&
                    $possibility->subType() === '*'
                ) {
                    return true;
                }

                if ($possibility->topLevel() !== $mediaType->topLevel()) {
                    return false;
                }

                if ($possibility->subType() === '*') {
                    return true;
                }

                return $possibility->subType() === $mediaType->subType();
            })
            ->sort(function(Pattern $a, Pattern $b): int {
                return $b->quality() <=> $a->quality();
            });

        if ($available->size() === 0) {
            throw new MediaTypeDoesntMatchAnyException;
        }

        return $available->first();
    }
}
