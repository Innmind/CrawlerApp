<?php
declare(strict_types = 1);

namespace Tests\Crawler\Translator\Property\HtmlPage;

use Crawler\Translator\{
    Property\HtmlPage\AuthorTranslator,
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

class AuthorTranslatorTest extends TestCase
{
    private $translator;
    private $property;

    public function setUp(): void
    {
        $this->translator = new AuthorTranslator;
        $this->property = new Property(
            'author',
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
                'author',
                new Attribute\Attribute('author', 'whatever')
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
                'author',
                new Attribute\Attribute('author', $expected = 'foo')
            ),
            $this->createMock(Readable::class)
        );

        $this->assertSame(
            $expected,
            $this->translator->translate($resource, $this->property)
        );
    }
}
