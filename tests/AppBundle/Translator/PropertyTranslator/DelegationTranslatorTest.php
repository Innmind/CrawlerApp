<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Translator\PropertyTranslator;

use AppBundle\Translator\{
    PropertyTranslator\DelegationTranslator,
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

class DelegationTranslatorTest extends TestCase
{
    private $translator;
    private $knownProperty;
    private $uknownProperty;
    private $host;
    private $foo;

    public function setUp()
    {
        $this->translator = new DelegationTranslator(
            (new Map('string', PropertyTranslatorInterface::class))
                ->put(
                    'host',
                    $this->host = $this->createMock(PropertyTranslatorInterface::class)
                )
                ->put(
                    'foo',
                    $this->foo = $this->createMock(PropertyTranslatorInterface::class)
                )
        );
        $this->knownProperty = new Property(
            'host',
            $this->createMock(TypeInterface::class),
            new Access(new Set('string')),
            new Set('string'),
            false
        );
        $this->unknownProperty = new Property(
            'foo',
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
        $this
            ->host
            ->expects($this->at(0))
            ->method('supports')
            ->with($resource, $this->knownProperty)
            ->willReturn(true);
        $this
            ->host
            ->expects($this->at(1))
            ->method('supports')
            ->with($resource, $this->knownProperty)
            ->willReturn(false);
        $this
            ->foo
            ->expects($this->at(0))
            ->method('supports')
            ->with($resource, $this->unknownProperty)
            ->willReturn(false);

        $this->assertTrue($this->translator->supports($resource, $this->knownProperty));
        $this->assertFalse($this->translator->supports($resource, $this->knownProperty));
        $this->assertFalse($this->translator->supports($resource, $this->unknownProperty));
    }

    public function testTranslate()
    {
        $resource = new HttpResource(
            $this->createMock(UrlInterface::class),
            $this->createMock(MediaTypeInterface::class),
            new Map('string', AttributeInterface::class),
            $this->createMock(StreamInterface::class)
        );
        $this
            ->host
            ->expects($this->once())
            ->method('translate')
            ->with($resource, $this->knownProperty)
            ->willReturn('baz');

        $this->assertSame(
            'baz',
            $this->translator->translate($resource, $this->knownProperty)
        );
    }

    /**
     * @expectedException AppBundle\Exception\InvalidArgumentException
     */
    public function testThrowWhenInvalidTranslatorMap()
    {
        new DelegationTranslator(new Map('string', 'object'));
    }
}
