<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Translator\Property;

use AppBundle\Translator\{
    Property\QueryTranslator,
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

class QueryTranslatorTest extends TestCase
{
    private $translator;
    private $property;

    public function setUp()
    {
        $this->translator = new QueryTranslator;
        $this->property = new Property(
            'query',
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
            Url::fromString('http://www.example.com/path?some=query'),
            $this->createMock(MediaType::class),
            new Map('string', Attribute::class),
            $this->createMock(Readable::class)
        );

        $this->assertSame(
            'some=query',
            $this->translator->translate($resource, $this->property)
        );
    }
}
