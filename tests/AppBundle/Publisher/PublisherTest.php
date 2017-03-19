<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Publisher;

use AppBundle\{
    Publisher\Publisher,
    PublisherInterface,
    Translator\HttpResourceTranslator,
    Translator\PropertyTranslatorInterface,
    Reference
};
use Innmind\Rest\Client\{
    ClientInterface,
    ServerInterface,
    Server\CapabilitiesInterface,
    Definition\HttpResource as Definition,
    Definition\Property as PropertyDefinition,
    Definition\Identity,
    HttpResource,
    IdentityInterface
};
use Innmind\Crawler\{
    HttpResource as CrawledResource,
    HttpResource\AttributeInterface,
    HttpResource\Attribute
};
use Innmind\Url\{
    UrlInterface,
    Url
};
use Innmind\Http\Message\RequestInterface;
use Innmind\Filesystem\{
    MediaTypeInterface,
    MediaType\MediaType,
    StreamInterface
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class PublisherTest extends TestCase
{
    private $publisher;
    private $client;

    public function setUp()
    {
        $this->publisher = new Publisher(
            $this->client = $this->createMock(ClientInterface::class),
            new HttpResourceTranslator(
                $this->createMock(PropertyTranslatorInterface::class)
            )
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            PublisherInterface::class,
            $this->publisher
        );
    }

    /**
     * @expectedException AppBundle\Exception\ResourceCannotBePublishedException
     */
    public function testThrowWhenNoApiResourceForCrawledResource()
    {
        $this
            ->client
            ->expects($this->once())
            ->method('server')
            ->with('http://some.server/')
            ->willReturn(
                $server = $this->createMock(ServerInterface::class)
            );
        $server
            ->expects($this->once())
            ->method('capabilities')
            ->willReturn(
                $capabilities = $this->createMock(CapabilitiesInterface::class)
            );
        $capabilities
            ->expects($this->once())
            ->method('definitions')
            ->willReturn(
                (new Map('string', Definition::class))
                    ->put(
                        'foo',
                        new Definition(
                            'foo',
                            $this->createMock(UrlInterface::class),
                            new Identity('uuid'),
                            new Map('string', PropertyDefinition::class),
                            new Map('scalar', 'variable'),
                            new Map('string', 'string'),
                            false
                        )
                    )
            );
        $resource = new CrawledResource(
            $this->createMock(UrlInterface::class),
            $this->createMock(MediaTypeInterface::class),
            new Map('string', AttributeInterface::class),
            $this->createMock(StreamInterface::class)
        );

        ($this->publisher)($resource, Url::fromString('http://some.server/'));
    }

    /**
     * @expectedException AppBundle\Exception\ResourceCannotBePublishedException
     */
    public function testThrowWhenNoApiResourceMatchingCrawledMediaType()
    {
        $this
            ->client
            ->expects($this->once())
            ->method('server')
            ->with('http://some.server/')
            ->willReturn(
                $server = $this->createMock(ServerInterface::class)
            );
        $server
            ->expects($this->once())
            ->method('capabilities')
            ->willReturn(
                $capabilities = $this->createMock(CapabilitiesInterface::class)
            );
        $capabilities
            ->expects($this->once())
            ->method('definitions')
            ->willReturn(
                (new Map('string', Definition::class))
                    ->put(
                        'foo',
                        new Definition(
                            'foo',
                            $this->createMock(UrlInterface::class),
                            new Identity('uuid'),
                            new Map('string', PropertyDefinition::class),
                            (new Map('scalar', 'variable'))
                                ->put('allowed_media_types', ['image/*']),
                            new Map('string', 'string'),
                            false
                        )
                    )
            );
        $resource = new CrawledResource(
            $this->createMock(UrlInterface::class),
            MediaType::fromString('text/html'),
            new Map('string', AttributeInterface::class),
            $this->createMock(StreamInterface::class)
        );

        ($this->publisher)($resource, Url::fromString('http://some.server/'));
    }

    public function testInvokation()
    {
        $serverUrl = Url::fromString('http://some.server/');
        $this
            ->client
            ->expects($this->once())
            ->method('server')
            ->with('http://some.server/')
            ->willReturn(
                $server = $this->createMock(ServerInterface::class)
            );
        $server
            ->expects($this->once())
            ->method('capabilities')
            ->willReturn(
                $capabilities = $this->createMock(CapabilitiesInterface::class)
            );
        $server
            ->expects($this->once())
            ->method('url')
            ->willReturn($serverUrl);
        $capabilities
            ->expects($this->once())
            ->method('definitions')
            ->willReturn(
                (new Map('string', Definition::class))
                    ->put(
                        'foo',
                        $definition = new Definition(
                            'foo',
                            $this->createMock(UrlInterface::class),
                            new Identity('uuid'),
                            new Map('string', PropertyDefinition::class),
                            (new Map('scalar', 'variable'))
                                ->put('allowed_media_types', ['image/*']),
                            new Map('string', 'string'),
                            false
                        )
                    )
                    ->put(
                        'bar',
                        new Definition(
                            'bar',
                            $this->createMock(UrlInterface::class),
                            new Identity('uuid'),
                            new Map('string', PropertyDefinition::class),
                            (new Map('scalar', 'variable'))
                                ->put('allowed_media_types', ['text/html']),
                            new Map('string', 'string'),
                            false
                        )
                    )
            );
        $server
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function(HttpResource $resource): bool {
                return $resource->name() === 'foo' &&
                    $resource->properties()->size() === 0;
            }))
            ->willReturn(
                $identity = $this->createMock(IdentityInterface::class)
            );
        $resource = new CrawledResource(
            $this->createMock(UrlInterface::class),
            MediaType::fromString('image/png'),
            new Map('string', AttributeInterface::class),
            $this->createMock(StreamInterface::class)
        );

        $reference = ($this->publisher)($resource, $serverUrl);

        $this->assertInstanceOf(Reference::class, $reference);
        $this->assertSame($identity, $reference->identity());
        $this->assertSame('foo', $reference->definition());
        $this->assertSame($serverUrl, $reference->server());
    }
}
