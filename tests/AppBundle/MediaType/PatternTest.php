<?php
declare(strict_types = 1);

namespace Tests\AppBundle\MediaType;

use AppBundle\MediaType\Pattern;
use Innmind\Filesystem\MediaType\MediaType;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class PatternTest extends TestCase
{
    public function testInterface()
    {
        $pattern = new Pattern(
            'application',
            'json',
            0.5
        );

        $this->assertSame('application', $pattern->topLevel());
        $this->assertSame('json', $pattern->subType());
        $this->assertSame(0.5, $pattern->quality());
        $this->assertSame('application/json; q=0.5', (string) $pattern);
    }

    /**
     * @expectedException AppBundle\Exception\DomainException
     */
    public function testThrowWhenNegativeQuality()
    {
        new Pattern('application', 'foo', -0.1);
    }

    /**
     * @expectedException AppBundle\Exception\DomainException
     */
    public function testThrowWhenQualityHigherThanOne()
    {
        new Pattern('application', 'foo', 1.1);
    }

    public function testFromString()
    {
        $pattern = Pattern::fromString(
            'application/tree.octet-stream+suffix;charset=UTF-8, another=param,me=too'
        );

        $this->assertInstanceOf(Pattern::class, $pattern);
        $this->assertSame('application', $pattern->topLevel());
        $this->assertSame('tree.octet-stream', $pattern->subType());
        $this->assertSame(1.0, $pattern->quality());
        $this->assertSame(
            'application/tree.octet-stream',
            (string) $pattern
        );

        $this->assertSame('*/*; q=0.1', (string) Pattern::fromString('*/*; q=0.1'));
        $this->assertSame('image/*; q=0.1', (string) Pattern::fromString('image/*; q=0.1'));
    }

    /**
     * @expectedException Innmind\Filesystem\Exception\InvalidMediaTypeString
     */
    public function testThrowWhenInvalidString()
    {
        Pattern::fromString('foo');
    }

    /**
     * @dataProvider cases
     */
    public function testMatches(bool $expected, string $pattern, string $media)
    {
        $this->assertSame(
            $expected,
            Pattern::fromString($pattern)->matches(
                MediaType::fromString($media)
            )
        );
    }

    public function cases(): array
    {
        return [
            [true, '*/*', 'text/html'],
            [true, '*/*', 'application/json'],
            [true, 'application/*', 'application/json'],
            [true, 'application/*', 'application/octet-stream'],
            [true, 'application/octet-stream', 'application/octet-stream'],
            [false, 'application/*', 'image/png'],
            [false, 'image/jpeg', 'image/png'],
        ];
    }
}
