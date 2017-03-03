<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Translator\PropertyTranslator;

use AppBundle\Translator\{
    PropertyTranslator\CharsetTranslator,
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
    HttpResource\Attribute
};
use Innmind\Url\UrlInterface;
use Innmind\Filesystem\{
    StreamInterface,
    MediaTypeInterface
};
use Innmind\Immutable\{
    Set,
    Map
};
use PHPUnit\Framework\TestCase;

class CharsetTranslatorTest extends TestCase
{
    private $translator;
    private $property;

    public function setUp()
    {
        $this->translator = new CharsetTranslator;
        $this->property = new Property(
            'charset',
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
                'charset',
                new Attribute('charset', 'whatever')
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
                'charset',
                new Attribute('charset', 'whatever')
            ),
            $this->createMock(StreamInterface::class)
        );

        $this->assertSame(
            'whatever',
            $this->translator->translate($resource, $this->property)
        );
    }
}
