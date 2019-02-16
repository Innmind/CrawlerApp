<?php
declare(strict_types = 1);

namespace Tests\Crawler\MediaType;

use Crawler\{
    MediaType\Negotiator,
    MediaType\Pattern,
    Exception\MediaTypeDoesntMatchAny,
};
use Innmind\Filesystem\MediaType\MediaType;
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class NegotiatorTest extends TestCase
{
    public function testBest()
    {
        $best = (new Negotiator)->best(
            MediaType::fromString('image/png'),
            (new Set(Pattern::class))
                ->add(Pattern::fromString('*/*; q=0.1'))
                ->add($expected = Pattern::fromString('image/*'))
                ->add(Pattern::fromString('image/png; q=0.5'))
                ->add(Pattern::fromString('text/html'))
        );

        $this->assertSame($expected, $best);
    }

    public function testBestIsFullWildcard()
    {
        $best = (new Negotiator)->best(
            MediaType::fromString('image/png'),
            (new Set(Pattern::class))
                ->add($expected = Pattern::fromString('*/*; q=0.1'))
                ->add(Pattern::fromString('text/html'))
        );

        $this->assertSame($expected, $best);
    }

    public function testBestIsWildcard()
    {
        $best = (new Negotiator)->best(
            MediaType::fromString('image/png'),
            (new Set(Pattern::class))
                ->add(Pattern::fromString('*/*; q=0.1'))
                ->add($expected = Pattern::fromString('image/*'))
                ->add(Pattern::fromString('text/html'))
        );

        $this->assertSame($expected, $best);
    }

    public function testBestIsSpecific()
    {
        $best = (new Negotiator)->best(
            MediaType::fromString('image/png'),
            (new Set(Pattern::class))
                ->add(Pattern::fromString('*/*; q=0.1'))
                ->add($expected = Pattern::fromString('image/png'))
                ->add(Pattern::fromString('text/html'))
        );

        $this->assertSame($expected, $best);
    }

    public function testThrowWhenNoMediaTypeFound()
    {
        $this->expectException(MediaTypeDoesntMatchAny::class);

        (new Negotiator)->best(
            MediaType::fromString('image/png'),
            (new Set(Pattern::class))
                ->add(Pattern::fromString('text/html'))
        );
    }
}
