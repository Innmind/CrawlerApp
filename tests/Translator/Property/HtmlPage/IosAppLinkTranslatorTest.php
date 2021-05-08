<?php
declare(strict_types = 1);

namespace Tests\Crawler\Translator\Property\HtmlPage;

use Crawler\Translator\{
    Property\HtmlPage\IosAppLinkTranslator,
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

class IosAppLinkTranslatorTest extends TestCase
{
    private $translator;
    private $property;

    protected function setUp(): void
    {
        $this->translator = new IosAppLinkTranslator;
        $this->property = new Property(
            'ios_app_link',
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
                'ios',
                new Attribute\Attribute('ios', 'whatever')
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
                'ios',
                new Attribute\Attribute('ios', 'innmind://')
            ),
            $this->createMock(Readable::class)
        );

        $this->assertSame(
            'innmind://',
            $this->translator->translate($resource, $this->property)
        );
    }
}
