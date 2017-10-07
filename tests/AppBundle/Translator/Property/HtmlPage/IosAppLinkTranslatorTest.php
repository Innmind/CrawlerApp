<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Translator\Property\HtmlPage;

use AppBundle\Translator\{
    Property\HtmlPage\IosAppLinkTranslator,
    PropertyTranslatorInterface
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

class IosAppLinkTranslatorTest extends TestCase
{
    private $translator;
    private $property;

    public function setUp()
    {
        $this->translator = new IosAppLinkTranslator;
        $this->property = new Property(
            'ios_app_link',
            $this->createMock(Type::class),
            new Access,
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
                'ios',
                new Attribute\Attribute('ios', 'whatever')
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
