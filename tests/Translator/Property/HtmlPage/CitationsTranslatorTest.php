<?php
declare(strict_types = 1);

namespace Tests\Crawler\Translator\Property\HtmlPage;

use Crawler\Translator\{
    Property\HtmlPage\CitationsTranslator,
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

class CitationsTranslatorTest extends TestCase
{
    private $translator;
    private $property;

    public function setUp(): void
    {
        $this->translator = new CitationsTranslator;
        $this->property = new Property(
            'citations',
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
        $attributes = Map::of('string', Attribute::class);
        $resource = new HttpResource(
            Url::of('example.com'),
            MediaType::null(),
            $attributes,
            $this->createMock(Readable::class)
        );

        $this->assertFalse($this->translator->supports($resource, $this->property));

        $resource = new HttpResource(
            Url::of('example.com'),
            MediaType::null(),
            $attributes->put(
                'citations',
                new Attribute\Attribute('citations', 'whatever')
            ),
            $this->createMock(Readable::class)
        );

        $this->assertTrue($this->translator->supports($resource, $this->property));
    }

    public function testTranslate()
    {
        $attributes = Map::of('string', Attribute::class);
        $resource = new HttpResource(
            Url::of('example.com'),
            MediaType::null(),
            $attributes->put(
                'citations',
                new Attribute\Attribute('citations', $expected = Set::of('string'))
            ),
            $this->createMock(Readable::class)
        );

        $this->assertSame(
            $expected,
            $this->translator->translate($resource, $this->property)
        );
    }
}
