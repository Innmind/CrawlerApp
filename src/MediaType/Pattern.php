<?php
declare(strict_types = 1);

namespace Crawler\MediaType;

use Crawler\Exception\DomainException;
use Innmind\Filesystem\{
    MediaType,
    Exception\InvalidMediaTypeString
};
use Innmind\Immutable\{
    Str,
    Map
};

final class Pattern
{
    private $topLevel;
    private $subType;
    private $quality;

    public function __construct(
        string $topLevel,
        string $subType,
        float $quality = 1
    ) {
        if ($quality < 0 || $quality > 1) {
            throw new DomainException;
        }

        $this->topLevel = $topLevel;
        $this->subType = $subType;
        $this->quality = $quality;
    }

    public function topLevel(): string
    {
        return $this->topLevel;
    }

    public function subType(): string
    {
        return $this->subType;
    }

    public function quality(): float
    {
        return $this->quality;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s/%s%s',
            $this->topLevel,
            $this->subType,
            $this->quality !== 1.0 ? '; q='.$this->quality : ''
        );
    }

    public function matches(MediaType $mediaType): bool
    {
        if (
            $this->topLevel() === '*' &&
            $this->subType() === '*'
        ) {
            return true;
        }

        if ($this->topLevel() !== $mediaType->topLevel()) {
            return false;
        }

        if ($this->subType() === '*') {
            return true;
        }

        return $this->subType() === $mediaType->subType();
    }

    /**
     * Build an object out of a string
     *
     * @param string $string
     *
     * @return self
     */
    public static function fromString(string $string): self
    {
        $string = new Str($string);
        $pattern = '~[\w\-.*]+/[\w\-.*]+([;,] [\w\-.]+=[\w\-.]+)?~';

        if (!$string->matches($pattern)) {
            throw new InvalidMediaTypeString;
        }

        $splits = $string->pregSplit('~[;,] ?~');
        $matches = $splits
            ->first()
            ->capture('~^(?<topLevel>[\w\-.*]+)/(?<subType>[\w\-.*]+)(\+(?<suffix>\w+))?$~');

        $topLevel = $matches->get('topLevel');
        $subType = $matches->get('subType');

        $params = $splits
            ->drop(1)
            ->reduce(
                new Map('string', 'string'),
                function(Map $carry, Str $param): Map {
                    $matches = $param->capture(
                        '~^(?<key>[\w\-.]+)=(?<value>[\w\-.]+)$~'
                    );

                    return $carry->put(
                        (string) $matches->get('key'),
                        (string) $matches->get('value')
                    );
                }
            );

        return new self(
            (string) $topLevel,
            (string) $subType,
            $params->contains('q') ? (float) $params->get('q') : 1
        );
    }
}
