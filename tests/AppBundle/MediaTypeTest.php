<?php
declare(strict_types = 1);

namespace Tests\AppBundle;

use AppBundle\{
    MediaType
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class MediaTypeTest extends TestCase
{
    public function testInterface()
    {
        $mediaType = new MediaType(
            'application',
            'json',
            0.5
        );

        $this->assertSame('application', $mediaType->topLevel());
        $this->assertSame('json', $mediaType->subType());
        $this->assertSame(0.5, $mediaType->quality());
        $this->assertSame('application/json; q=0.5', (string) $mediaType);
    }

    /**
     * @expectedException AppBundle\Exception\InvalidArgumentException
     */
    public function testThrowWhenNegativeQuality()
    {
        new MediaType('application', 'foo', -0.1);
    }

    /**
     * @expectedException AppBundle\Exception\InvalidArgumentException
     */
    public function testThrowWhenQualityHigherThanOne()
    {
        new MediaType('application', 'foo', 1.1);
    }

    public function testFromString()
    {
        $mediaType = MediaType::fromString(
            'application/tree.octet-stream+suffix;charset=UTF-8, another=param,me=too'
        );

        $this->assertInstanceOf(MediaType::class, $mediaType);
        $this->assertSame('application', $mediaType->topLevel());
        $this->assertSame('tree.octet-stream', $mediaType->subType());
        $this->assertSame(1.0, $mediaType->quality());
        $this->assertSame(
            'application/tree.octet-stream',
            (string) $mediaType
        );

        $this->assertSame('*/*; q=0.1', (string) MediaType::fromString('*/*; q=0.1'));
        $this->assertSame('image/*; q=0.1', (string) MediaType::fromString('image/*; q=0.1'));
    }

    /**
     * @expectedException Innmind\Filesystem\Exception\InvalidMediaTypeStringException
     */
    public function testThrowWhenInvalidMediaTypeString()
    {
        MediaType::fromString('foo');
    }
}
