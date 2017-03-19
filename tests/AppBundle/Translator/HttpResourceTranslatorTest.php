<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Translator;

use AppBundle\Translator\{
    HttpResourceTranslator,
    PropertyTranslatorInterface
};
use Innmind\Crawler\{
    HttpResource as CrawledResource,
    HttpResource\AttributeInterface,
    HttpResource\Attribute
};
use Innmind\Rest\Client\{
    Definition\HttpResource as Definition,
    Definition\Identity,
    Definition\Property,
    Definition\TypeInterface,
    Definition\Access,
    HttpResource
};
use Innmind\Url\UrlInterface;
use Innmind\Filesystem\{
    MediaTypeInterface,
    StreamInterface
};
use Innmind\Immutable\{
    Map,
    Set
};
use PHPUnit\Framework\TestCase;

class HttpResourceTranslatorTest extends TestCase
{
    public function testTranslate()
    {
        $crawledResource = new CrawledResource(
            $this->createMock(UrlInterface::class),
            $this->createMock(MediaTypeInterface::class),
            (new Map('string', AttributeInterface::class))
                ->put('wanted', new Attribute('wanted', true))
                ->put('not_wanted', new Attribute('not_wanted', false)),
            $this->createMock(StreamInterface::class)
        );
        $definition = new Definition(
            'foo',
            $this->createMock(UrlInterface::class),
            new Identity('uuid'),
            (new Map('string', Property::class))
                ->put(
                    'wanted',
                    $wanted = new Property(
                        'wanted',
                        $this->createMock(TypeInterface::class),
                        new Access(new Set('string')),
                        new Set('string'),
                        false
                    )
                )
                ->put(
                    'not_wanted',
                    $notWanted = new Property(
                        'not_wanted',
                        $this->createMock(TypeInterface::class),
                        new Access(new Set('string')),
                        new Set('string'),
                        false
                    )
                ),
            new Map('scalar', 'variable'),
            new Map('string', 'string'),
            false
        );
        $propertyTranslator = $this->createMock(PropertyTranslatorInterface::class);
        $propertyTranslator
            ->expects($this->at(0))
            ->method('supports')
            ->with($crawledResource, $wanted)
            ->willReturn(true);
        $propertyTranslator
            ->expects($this->at(1))
            ->method('supports')
            ->with($crawledResource, $notWanted)
            ->willReturn(false);
        $propertyTranslator
            ->expects($this->once())
            ->method('translate')
            ->with($crawledResource, $wanted)
            ->willReturn('some value');

        $resource = (new HttpResourceTranslator($propertyTranslator))->translate(
            $crawledResource,
            $definition
        );

        $this->assertInstanceOf(HttpResource::class, $resource);
        $this->assertSame('foo', $resource->name());
        $this->assertCount(1, $resource->properties());
        $this->assertSame('some value', $resource->properties()->get('wanted')->value());
    }
}
