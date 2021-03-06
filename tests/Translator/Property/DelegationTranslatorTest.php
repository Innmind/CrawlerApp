<?php
declare(strict_types = 1);

namespace Tests\Crawler\Translator\Property;

use Crawler\Translator\{
    Property\DelegationTranslator,
    PropertyTranslator,
};
use Innmind\Rest\Client\Definition\{
    Property,
    Type,
    Access,
};
use Innmind\Crawler\{
    HttpResource,
    HttpResource\Attribute,
};
use Innmind\Url\Url;
use Innmind\MediaType\MediaType;
use Innmind\Stream\Readable;
use Innmind\Immutable\{
    Set,
    Map,
};
use PHPUnit\Framework\TestCase;

class DelegationTranslatorTest extends TestCase
{
    private $translator;
    private $knownProperty;
    private $uknownProperty;
    private $host;
    private $foo;

    public function setUp(): void
    {
        $this->translator = new DelegationTranslator(
            Map::of('string', PropertyTranslator::class)
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
            Set::of('string'),
            false
        );
        $this->unknownProperty = new Property(
            'foo',
            $this->createMock(Type::class),
            new Access,
            Set::of('string'),
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
            Url::of('example.com'),
            MediaType::null(),
            Map::of('string', Attribute::class),
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
            Url::of('example.com'),
            MediaType::null(),
            Map::of('string', Attribute::class),
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

    public function testThrowWhenInvalidTranslatorMap()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type Map<string, Crawler\Translator\PropertyTranslator>');

        new DelegationTranslator(Map::of('string', 'object'));
    }
}
