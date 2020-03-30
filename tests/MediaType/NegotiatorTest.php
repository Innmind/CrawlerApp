<?php
declare(strict_types = 1);

namespace Tests\Crawler\MediaType;

use Crawler\{
    MediaType\Negotiator,
    MediaType\Pattern,
    Exception\MediaTypeDoesntMatchAny,
};
use Innmind\MediaType\MediaType;
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class NegotiatorTest extends TestCase
{
    public function testBest()
    {
        $best = (new Negotiator)->best(
            MediaType::of('image/png'),
            Set::of(
                Pattern::class,
                Pattern::of('*/*; q=0.1'),
                $expected = Pattern::of('image/*'),
                Pattern::of('image/png; q=0.5'),
                Pattern::of('text/html')
            )
        );

        $this->assertSame($expected, $best);
    }

    public function testBestIsFullWildcard()
    {
        $best = (new Negotiator)->best(
            MediaType::of('image/png'),
            Set::of(
                Pattern::class,
                $expected = Pattern::of('*/*; q=0.1'),
                Pattern::of('text/html')
            )
        );

        $this->assertSame($expected, $best);
    }

    public function testBestIsWildcard()
    {
        $best = (new Negotiator)->best(
            MediaType::of('image/png'),
            Set::of(
                Pattern::class,
                Pattern::of('*/*; q=0.1'),
                $expected = Pattern::of('image/*'),
                Pattern::of('text/html')
            )
        );

        $this->assertSame($expected, $best);
    }

    public function testBestIsSpecific()
    {
        $best = (new Negotiator)->best(
            MediaType::of('image/png'),
            Set::of(
                Pattern::class,
                Pattern::of('*/*; q=0.1'),
                $expected = Pattern::of('image/png'),
                Pattern::of('text/html')
            )
        );

        $this->assertSame($expected, $best);
    }

    public function testThrowWhenNoMediaTypeFound()
    {
        $this->expectException(MediaTypeDoesntMatchAny::class);

        (new Negotiator)->best(
            MediaType::of('image/png'),
            Set::of(Pattern::class, Pattern::of('text/html'))
        );
    }
}
