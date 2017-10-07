<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Translator\Property;

use AppBundle\Translator\{
    Property\PathTranslator,
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

class PathTranslatorTest extends TestCase
{
    private $translator;
    private $property;

    public function setUp()
    {
        $this->translator = new PathTranslator;
        $this->property = new Property(
            'path',
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
        $resource = new HttpResource(
            $this->createMock(UrlInterface::class),
            $this->createMock(MediaType::class),
            new Map('string', Attribute::class),
            $this->createMock(Readable::class)
        );

        $this->assertTrue($this->translator->supports($resource, $this->property));
    }

    public function testTranslate()
    {
        $resource = new HttpResource(
            Url::fromString('http://www.example.com/path'),
            $this->createMock(MediaType::class),
            new Map('string', Attribute::class),
            $this->createMock(Readable::class)
        );

        $this->assertSame(
            '/path',
            $this->translator->translate($resource, $this->property)
        );
    }
}
