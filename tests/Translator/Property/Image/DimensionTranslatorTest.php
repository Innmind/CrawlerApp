<?php
declare(strict_types = 1);

namespace Tests\Crawler\Translator\Property\Image;

use Crawler\Translator\{
    Property\Image\DimensionTranslator,
    PropertyTranslator
};
use Innmind\Rest\Client\Definition\{
    Property,
    Type,
    Access
};
use Innmind\Crawler\{
    HttpResource,
    HttpResource\Attribute,
    HttpResource\Attributes\Attributes
};
use Innmind\Url\UrlInterface;
use Innmind\Filesystem\MediaType;
use Innmind\Stream\Readable;
use Innmind\Immutable\{
    Set,
    Map,
    MapInterface
};
use PHPUnit\Framework\TestCase;

class DimensionTranslatorTest extends TestCase
{
    private $translator;
    private $property;

    public function setUp(): void
    {
        $this->translator = new DimensionTranslator;
        $this->property = new Property(
            'dimension',
            $this->createMock(Type::class),
            new Access,
            new Set('string'),
            false
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            PropertyTranslator::class,
            $this->translator
        );
    }

    public function testSupports()
    {
        $attributes = new Map('string', Attribute::class);
        $resource = new HttpResource(
            $this->createMock(UrlInterface::class),
            $this->createMock(MediaType::class),
            $attributes,
            $this->createMock(Readable::class)
        );

        $this->assertFalse($this->translator->supports($resource, $this->property));

        $resource = new HttpResource(
            $this->createMock(UrlInterface::class),
            $this->createMock(MediaType::class),
            $attributes->put(
                'dimension',
                new Attribute\Attribute('dimension', 'whatever')
            ),
            $this->createMock(Readable::class)
        );

        $this->assertTrue($this->translator->supports($resource, $this->property));
    }

    public function testTranslate()
    {
        $attributes = new Map('string', Attribute::class);
        $resource = new HttpResource(
            $this->createMock(UrlInterface::class),
            $this->createMock(MediaType::class),
            $attributes->put(
                'dimension',
                new Attributes(
                    'dimension',
                    (new Map('string', Attribute::class))
                        ->put('width', new Attribute\Attribute('width', 24))
                        ->put('height', new Attribute\Attribute('height', 42))
                )
            ),
            $this->createMock(Readable::class)
        );

        $map = $this->translator->translate($resource, $this->property);

        $this->assertInstanceOf(MapInterface::class, $map);
        $this->assertSame('string', (string) $map->keyType());
        $this->assertSame('int', (string) $map->valueType());
        $this->assertCount(2, $map);
        $this->assertSame(24, $map->get('width'));
        $this->assertSame(42, $map->get('height'));
    }
}
