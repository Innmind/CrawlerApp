<?php
declare(strict_types = 1);

namespace AppBundle;

use AppBundle\Exception\MediaTypeDoesntMatchAnyException;
use Innmind\Immutable\SetInterface;

final class Negotiator
{
    /**
     * @param SetInterface<MediaType> $available
     */
    public function best(MediaType $mediaType, SetInterface $available): MediaType
    {
        $available = $available
            ->filter(function(MediaType $possibility) use ($mediaType): bool {
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
            ->sort(function(MediaType $a, MediaType $b): int {
                return $b->quality() <=> $a->quality();
            });

        if ($available->size() === 0) {
            throw new MediaTypeDoesntMatchAnyException;
        }

        return $available->first();
    }
}
