<?php
declare(strict_types = 1);

namespace Tests\Crawler\Translator\Property\HtmlPage;

use Crawler\Translator\{
    Property\HtmlPage\ThemeColourTranslator,
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
use Innmind\Url\UrlInterface;
use Innmind\Filesystem\MediaType;
use Innmind\Stream\Readable;
use Innmind\Colour\Colour;
use Innmind\Immutable\{
    Set,
    Map,
};
use PHPUnit\Framework\TestCase;

class ThemeColourTranslatorTest extends TestCase
{
    private $translator;
    private $property;

    public function setUp(): void
    {
        $this->translator = new ThemeColourTranslator;
        $this->property = new Property(
            'theme_colour',
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
                'theme-color',
                new Attribute\Attribute('theme-color', Colour::fromString('39f'))
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
                'theme-color',
                new Attribute\Attribute('theme-color', Colour::fromString('39f'))
            ),
            $this->createMock(Readable::class)
        );

        $this->assertSame(
            '#3399ff',
            $this->translator->translate($resource, $this->property)
        );
    }
}
