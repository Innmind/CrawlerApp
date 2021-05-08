<?php
declare(strict_types = 1);

namespace Tests\Crawler\Translator\Property;

use Crawler\Translator\{
    Property\HostTranslator,
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

class HostTranslatorTest extends TestCase
{
    private $translator;
    private $property;

    protected function setUp(): void
    {
        $this->translator = new HostTranslator;
        $this->property = new Property(
            'host',
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

        $this->assertTrue($this->translator->supports($resource, $this->property));
    }

    public function testTranslate()
    {
        $resource = new HttpResource(
            Url::of('http://www.example.com/path'),
            MediaType::null(),
            Map::of('string', Attribute::class),
            $this->createMock(Readable::class)
        );

        $this->assertSame(
            'www.example.com',
            $this->translator->translate($resource, $this->property)
        );
    }
}
