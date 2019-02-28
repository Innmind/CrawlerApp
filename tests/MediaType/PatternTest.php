<?php
declare(strict_types = 1);

namespace Tests\Crawler\MediaType;

use Crawler\{
    MediaType\Pattern,
    Exception\DomainException,
};
use Innmind\Filesystem\{
    MediaType\MediaType,
    Exception\InvalidMediaTypeString,
};
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

    public function testDefaultQuality()
    {
        $this->assertSame(1.0, (new Pattern('application', 'json'))->quality());
    }

    public function testAllowsQualityBounds()
    {
        $this->assertSame(0.0, (new Pattern('*', '*', 0))->quality());
        $this->assertSame(1.0, (new Pattern('*', '*', 1))->quality());
    }

    public function testThrowWhenNegativeQuality()
    {
        $this->expectException(DomainException::class);

        new Pattern('application', 'foo', -0.1);
    }

    public function testThrowWhenQualityHigherThanOne()
    {
        $this->expectException(DomainException::class);

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

    public function testThrowWhenInvalidString()
    {
        $this->expectException(InvalidMediaTypeString::class);
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
