<?php
declare(strict_types = 1);

namespace Tests\Crawler\Translator;

use Crawler\Translator\{
    HttpResourceTranslator,
    PropertyTranslator,
};
use Innmind\Crawler\{
    HttpResource as CrawledResource,
    HttpResource\Attribute,
};
use Innmind\Rest\Client\{
    Definition\HttpResource as Definition,
    Definition\Identity,
    Definition\Property,
    Definition\Type,
    Definition\Access,
    Definition\AllowedLink,
    HttpResource,
};
use Innmind\Url\Url;
use Innmind\MediaType\MediaType;
use Innmind\Stream\Readable;
use Innmind\Immutable\{
    Map,
    Set,
};
use PHPUnit\Framework\TestCase;

class HttpResourceTranslatorTest extends TestCase
{
    public function testTranslate()
    {
        $crawledResource = new CrawledResource(
            Url::of('example.com'),
            MediaType::null(),
            Map::of('string', Attribute::class)
                ('wanted', new Attribute\Attribute('wanted', true))
                ('not_wanted', new Attribute\Attribute('not_wanted', false)),
            $this->createMock(Readable::class)
        );
        $definition = new Definition(
            'foo',
            Url::of('example.com'),
            new Identity('uuid'),
            Map::of('string', Property::class)
                (
                    'wanted',
                    $wanted = new Property(
                        'wanted',
                        $this->createMock(Type::class),
                        new Access,
                        Set::of('string'),
                        false
                    )
                )
                (
                    'not_wanted',
                    $notWanted = new Property(
                        'not_wanted',
                        $this->createMock(Type::class),
                        new Access,
                        Set::of('string'),
                        false
                    )
                ),
            Map::of('scalar', 'scalar|array'),
            Set::of(AllowedLink::class),
            false
        );
        $propertyTranslator = $this->createMock(PropertyTranslator::class);
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
