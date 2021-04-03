<?php
declare(strict_types = 1);

namespace Tests\Crawler\Publisher;

use Crawler\{
    Publisher\CanonicalAwarePublisher,
    Publisher,
    Reference,
    AMQP\Message\Canonical,
};
use Innmind\Crawler\{
    HttpResource as CrawledResource,
    HttpResource\Attribute,
};
use Innmind\Url\Url;
use Innmind\MediaType\MediaType;
use Innmind\Stream\Readable;
use Innmind\Rest\Client\Identity;
use Innmind\AMQP\Producer;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class CanonicalAwarePublisherTest extends TestCase
{
    private $publisher;
    private $inner;
    private $producer;

    public function setUp(): void
    {
        $this->publisher = new CanonicalAwarePublisher(
            $this->inner = $this->createMock(Publisher::class),
            $this->producer = $this->createMock(Producer::class)
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            Publisher::class,
            $this->publisher
        );
    }

    public function testDoesntPublishWhenNoCanonical()
    {
        $resource = new CrawledResource(
            Url::of('example.com'),
            MediaType::null(),
            Map::of('string', Attribute::class),
            $this->createMock(Readable::class)
        );
        $server = Url::of('example.com');
        $this
            ->inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($resource, $server)
            ->willReturn(
                $expected = new Reference(
                    $this->createMock(Identity::class),
                    'foo',
                    Url::of('example.com')
                )
            );
        $this
            ->producer
            ->expects($this->never())
            ->method('__invoke');

        $this->assertSame($expected, ($this->publisher)($resource, $server));
    }

    public function testDoesntPublishWhenCanonicalIdenticalToResourceUrl()
    {
        $resource = new CrawledResource(
            $url = Url::of('http://example.com/'),
            MediaType::null(),
            Map::of('string', Attribute::class)
                (
                    'canonical',
                    new Attribute\Attribute('canonical', $url)
                ),
            $this->createMock(Readable::class)
        );
        $server = Url::of('example.com');
        $this
            ->inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($resource, $server)
            ->willReturn(
                $expected = new Reference(
                    $this->createMock(Identity::class),
                    'foo',
                    $server
                )
            );
        $this
            ->producer
            ->expects($this->never())
            ->method('__invoke');

        $this->assertSame($expected, ($this->publisher)($resource, $server));
    }

    public function testPublish()
    {
        $resource = new CrawledResource(
            Url::of('http://example.com/'),
            MediaType::null(),
            Map::of('string', Attribute::class)
                (
                    'canonical',
                    new Attribute\Attribute(
                        'canonical',
                        $published = Url::of('http://example.com/foo')
                    )
                ),
            $this->createMock(Readable::class)
        );
        $server = Url::of('http://server.url/');
        $this
            ->inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($resource, $server)
            ->willReturn(
                $expected = new Reference(
                    $identity = $this->createMock(Identity::class),
                    'foo',
                    $server
                )
            );
        $identity
            ->expects($this->once())
            ->method('toString')
            ->willReturn('some identity');
        $this
            ->producer
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function(Canonical $message) use ($published, $expected) {
                return $message->resource() === $published &&
                    $message->reference() === $expected;
            }));

        $this->assertSame($expected, ($this->publisher)($resource, $server));
    }
}
