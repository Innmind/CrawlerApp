<?php
declare(strict_types = 1);

namespace Tests\AppBundle;

use AppBundle\{
    Negotiator,
    MediaType
};
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class NegotiatorTest extends TestCase
{
    public function testBest()
    {
        $best = (new Negotiator)->best(
            MediaType::fromString('image/png'),
            (new Set(MediaType::class))
                ->add(MediaType::fromString('*/*; q=0.1'))
                ->add($expected = MediaType::fromString('image/*'))
                ->add(MediaType::fromString('image/png; q=0.5'))
                ->add(MediaType::fromString('text/html'))
        );

        $this->assertSame($expected, $best);
    }

    public function testBestIsFullWildcard()
    {
        $best = (new Negotiator)->best(
            MediaType::fromString('image/png'),
            (new Set(MediaType::class))
                ->add($expected = MediaType::fromString('*/*; q=0.1'))
                ->add(MediaType::fromString('text/html'))
        );

        $this->assertSame($expected, $best);
    }

    public function testBestIsWildcard()
    {
        $best = (new Negotiator)->best(
            MediaType::fromString('image/png'),
            (new Set(MediaType::class))
                ->add(MediaType::fromString('*/*; q=0.1'))
                ->add($expected = MediaType::fromString('image/*'))
                ->add(MediaType::fromString('text/html'))
        );

        $this->assertSame($expected, $best);
    }

    public function testBestIsSpecific()
    {
        $best = (new Negotiator)->best(
            MediaType::fromString('image/png'),
            (new Set(MediaType::class))
                ->add(MediaType::fromString('*/*; q=0.1'))
                ->add($expected = MediaType::fromString('image/png'))
                ->add(MediaType::fromString('text/html'))
        );

        $this->assertSame($expected, $best);
    }

    /**
     * @expectedException AppBundle\Exception\MediaTypeDoesntMatchAnyException
     */
    public function testThrowWhenNoMediaTypeFound()
    {
        (new Negotiator)->best(
            MediaType::fromString('image/png'),
            (new Set(MediaType::class))
                ->add(MediaType::fromString('text/html'))
        );
    }
}
