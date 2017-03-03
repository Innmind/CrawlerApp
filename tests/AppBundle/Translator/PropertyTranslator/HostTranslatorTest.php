<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Translator\PropertyTranslator;

use AppBundle\Translator\{
    PropertyTranslator\HostTranslator,
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
use Innmind\Url\{
    UrlInterface,
    Url
};
use Innmind\Filesystem\{
    StreamInterface,
    MediaTypeInterface
};
use Innmind\Immutable\{
    Set,
    Map
};
use PHPUnit\Framework\TestCase;

class HostTranslatorTest extends TestCase
{
    private $translator;
    private $property;

    public function setUp()
    {
        $this->translator = new HostTranslator;
        $this->property = new Property(
            'host',
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
        $resource = new HttpResource(
            $this->createMock(UrlInterface::class),
            $this->createMock(MediaTypeInterface::class),
            new Map('string', AttributeInterface::class),
            $this->createMock(StreamInterface::class)
        );

        $this->assertTrue($this->translator->supports($resource, $this->property));
    }

    public function testTranslate()
    {
        $resource = new HttpResource(
            Url::fromString('http://www.example.com/path'),
            $this->createMock(MediaTypeInterface::class),
            new Map('string', AttributeInterface::class),
            $this->createMock(StreamInterface::class)
        );

        $this->assertSame(
            'www.example.com',
            $this->translator->translate($resource, $this->property)
        );
    }
}
