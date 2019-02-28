<?php
declare(strict_types = 1);

namespace Tests\Crawler\Publisher;

use Crawler\{
    Publisher\Publisher,
    Publisher as PublisherInterface,
    Translator\HttpResourceTranslator,
    Translator\PropertyTranslator,
    Reference,
    Exception\ResourceCannotBePublished,
};
use Innmind\Rest\Client\{
    Client,
    Server,
    Server\Capabilities,
    Definition\HttpResource as Definition,
    Definition\Property as PropertyDefinition,
    Definition\Identity,
    Definition\AllowedLink,
    HttpResource,
    Identity as IdentityInterface,
};
use Innmind\Crawler\{
    HttpResource as CrawledResource,
    HttpResource\Attribute,
};
use Innmind\Url\{
    UrlInterface,
    Url,
};
use Innmind\Filesystem\MediaType;
use Innmind\Stream\Readable;
use Innmind\Immutable\{
    Map,
    Set,
};
use PHPUnit\Framework\TestCase;

class PublisherTest extends TestCase
{
    private $publisher;
    private $client;

    public function setUp(): void
    {
        $this->publisher = new Publisher(
            $this->client = $this->createMock(Client::class),
            new HttpResourceTranslator(
                $this->createMock(PropertyTranslator::class)
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

    public function testThrowWhenNoApiResourceForCrawledResource()
    {
        $this
            ->client
            ->expects($this->once())
            ->method('server')
            ->with('http://some.server/')
            ->willReturn(
                $server = $this->createMock(Server::class)
            );
        $server
            ->expects($this->once())
            ->method('capabilities')
            ->willReturn(
                $capabilities = $this->createMock(Capabilities::class)
            );
        $capabilities
            ->expects($this->once())
            ->method('definitions')
            ->willReturn(
                Map::of('string', Definition::class)
                    (
                        'foo',
                        new Definition(
                            'foo',
                            $this->createMock(UrlInterface::class),
                            new Identity('uuid'),
                            new Map('string', PropertyDefinition::class),
                            new Map('scalar', 'variable'),
                            new Set(AllowedLink::class),
                            false
                        )
                    )
            );
        $resource = new CrawledResource(
            $this->createMock(UrlInterface::class),
            $this->createMock(MediaType::class),
            new Map('string', Attribute::class),
            $this->createMock(Readable::class)
        );

        try {
            ($this->publisher)($resource, Url::fromString('http://some.server/'));
            $this->fail('it should throw');
        } catch (ResourceCannotBePublished $e) {
            $this->assertSame($resource, $e->resource());
        }
    }

    public function testThrowWhenNoApiResourceMatchingCrawledMediaType()
    {
        $this
            ->client
            ->expects($this->once())
            ->method('server')
            ->with('http://some.server/')
            ->willReturn(
                $server = $this->createMock(Server::class)
            );
        $server
            ->expects($this->once())
            ->method('capabilities')
            ->willReturn(
                $capabilities = $this->createMock(Capabilities::class)
            );
        $capabilities
            ->expects($this->once())
            ->method('definitions')
            ->willReturn(
                Map::of('string', Definition::class)
                    (
                        'foo',
                        new Definition(
                            'foo',
                            $this->createMock(UrlInterface::class),
                            new Identity('uuid'),
                            new Map('string', PropertyDefinition::class),
                            Map::of('scalar', 'variable')
                                ('allowed_media_types', ['image/*']),
                            new Set(AllowedLink::class),
                            false
                        )
                    )
            );
        $resource = new CrawledResource(
            $this->createMock(UrlInterface::class),
            MediaType\MediaType::fromString('text/html'),
            new Map('string', Attribute::class),
            $this->createMock(Readable::class)
        );

        try {
            ($this->publisher)($resource, Url::fromString('http://some.server/'));
            $this->fail('it should throw');
        } catch (ResourceCannotBePublished $e) {
            $this->assertSame($resource, $e->resource());
        }
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
                $server = $this->createMock(Server::class)
            );
        $server
            ->expects($this->once())
            ->method('capabilities')
            ->willReturn(
                $capabilities = $this->createMock(Capabilities::class)
            );
        $server
            ->expects($this->once())
            ->method('url')
            ->willReturn($serverUrl);
        $capabilities
            ->expects($this->once())
            ->method('definitions')
            ->willReturn(
                Map::of('string', Definition::class)
                    (
                        'foo',
                        $definition = new Definition(
                            'foo',
                            $this->createMock(UrlInterface::class),
                            new Identity('uuid'),
                            new Map('string', PropertyDefinition::class),
                            Map::of('scalar', 'variable')
                                ('allowed_media_types', ['image/*']),
                            new Set(AllowedLink::class),
                            false
                        )
                    )
                    (
                        'bar',
                        new Definition(
                            'bar',
                            $this->createMock(UrlInterface::class),
                            new Identity('uuid'),
                            new Map('string', PropertyDefinition::class),
                            Map::of('scalar', 'variable')
                                ('allowed_media_types', ['text/html']),
                            new Set(AllowedLink::class),
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
            MediaType\MediaType::fromString('image/png'),
            new Map('string', Attribute::class),
            $this->createMock(Readable::class)
        );

        $reference = ($this->publisher)($resource, $serverUrl);

        $this->assertInstanceOf(Reference::class, $reference);
        $this->assertSame($identity, $reference->identity());
        $this->assertSame('foo', $reference->definition());
        $this->assertSame($serverUrl, $reference->server());
    }
}
