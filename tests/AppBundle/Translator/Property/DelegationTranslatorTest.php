<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Translator\Property;

use AppBundle\Translator\{
    Property\DelegationTranslator,
    PropertyTranslator
};
use Innmind\Rest\Client\Definition\{
    Property,
    Type,
    Access
};
use Innmind\Crawler\{
    HttpResource,
    HttpResource\Attribute
};
use Innmind\Url\UrlInterface;
use Innmind\Filesystem\MediaType;
use Innmind\Stream\Readable;
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
            (new Map('string', PropertyTranslator::class))
                ->put(
                    'host',
                    $this->host = $this->createMock(PropertyTranslator::class)
                )
                ->put(
                    'foo',
                    $this->foo = $this->createMock(PropertyTranslator::class)
                )
        );
        $this->knownProperty = new Property(
            'host',
            $this->createMock(Type::class),
            new Access,
            new Set('string'),
            false
        );
        $this->unknownProperty = new Property(
            'foo',
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
        $resource = new HttpResource(
            $this->createMock(UrlInterface::class),
            $this->createMock(MediaType::class),
            new Map('string', Attribute::class),
            $this->createMock(Readable::class)
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
            $this->createMock(MediaType::class),
            new Map('string', Attribute::class),
            $this->createMock(Readable::class)
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
     * @expectedException TypeError
     * @expectedExceptionMessage Argument 1 must be of type MapInterface<string, AppBundle\Translator\PropertyTranslator>
     */
    public function testThrowWhenInvalidTranslatorMap()
    {
        new DelegationTranslator(new Map('string', 'object'));
    }
}
