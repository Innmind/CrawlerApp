<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Translator\Property\HtmlPage;

use AppBundle\Translator\{
    Property\HtmlPage\AndroidAppLinkTranslator,
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
use Innmind\Url\{
    UrlInterface,
    Url
};
use Innmind\Filesystem\MediaType;
use Innmind\Stream\Readable;
use Innmind\Immutable\{
    Set,
    Map
};
use PHPUnit\Framework\TestCase;

class AndroidAppLinkTranslatorTest extends TestCase
{
    private $translator;
    private $property;

    public function setUp()
    {
        $this->translator = new AndroidAppLinkTranslator;
        $this->property = new Property(
            'android_app_link',
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
                'android',
                new Attribute\Attribute('android', 'whatever')
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
                'android',
                new Attribute\Attribute('android', Url::fromString('android-app:///some/path'))
            ),
            $this->createMock(Readable::class)
        );

        $this->assertSame(
            'android-app:///some/path',
            $this->translator->translate($resource, $this->property)
        );
    }
}
