<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Translator\PropertyTranslator\Image;

use AppBundle\Translator\{
    PropertyTranslator\Image\DimensionTranslator,
    PropertyTranslatorInterface
};
use Innmind\Rest\Client\Definition\{
    Property,
    TypeInterface,
    Access
};
use Innmind\Crawler\{
    HttpResource,
    HttpResource\AttributeInterface,
    HttpResource\Attribute,
    HttpResource\Attributes
};
use Innmind\Url\UrlInterface;
use Innmind\Filesystem\{
    StreamInterface,
    MediaTypeInterface
};
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

    public function setUp()
    {
        $this->translator = new DimensionTranslator;
        $this->property = new Property(
            'dimension',
            $this->createMock(TypeInterface::class),
            new Access(new Set('string')),
            new Set('string'),
            false
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            PropertyTranslatorInterface::class,
            $this->translator
        );
    }

    public function testSupports()
    {
        $attributes = new Map('string', AttributeInterface::class);
        $resource = new HttpResource(
            $this->createMock(UrlInterface::class),
            $this->createMock(MediaTypeInterface::class),
            $attributes,
            $this->createMock(StreamInterface::class)
        );

        $this->assertFalse($this->translator->supports($resource, $this->property));

        $resource = new HttpResource(
            $this->createMock(UrlInterface::class),
            $this->createMock(MediaTypeInterface::class),
            $attributes->put(
                'dimension',
                new Attribute('dimension', 'whatever')
            ),
            $this->createMock(StreamInterface::class)
        );

        $this->assertTrue($this->translator->supports($resource, $this->property));
    }

    public function testTranslate()
    {
        $attributes = new Map('string', AttributeInterface::class);
        $resource = new HttpResource(
            $this->createMock(UrlInterface::class),
            $this->createMock(MediaTypeInterface::class),
            $attributes->put(
                'dimension',
                new Attributes(
                    'dimension',
                    (new Map('string', AttributeInterface::class))
                        ->put('width', new Attribute('width', 24))
                        ->put('height', new Attribute('height', 42))
                )
            ),
            $this->createMock(StreamInterface::class)
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
