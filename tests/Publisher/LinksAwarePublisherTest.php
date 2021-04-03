<?php
declare(strict_types = 1);

namespace Tests\Crawler\Publisher;

use Crawler\{
    Publisher\LinksAwarePublisher,
    Publisher,
    Reference,
    AMQP\Message\Link,
};
use Innmind\Crawler\{
    HttpResource as CrawledResource,
    HttpResource\Attribute,
};
use Innmind\Url\{
    Url,
    Fragment,
};
use Innmind\MediaType\MediaType;
use Innmind\Stream\Readable;
use Innmind\Rest\Client\Identity;
use Innmind\AMQP\Producer;
use Innmind\Immutable\{
    Map,
    Set,
};
use PHPUnit\Framework\TestCase;

class LinksAwarePublisherTest extends TestCase
{
    private $publisher;
    private $inner;
    private $producer;

    public function setUp(): void
    {
        $this->publisher = new LinksAwarePublisher(
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

    public function testDoesntPublishWhenNoLink()
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
                    $server
                )
            );
        $this
            ->producer
            ->expects($this->never())
            ->method('__invoke');

        $this->assertSame($expected, ($this->publisher)($resource, $server));
    }

    public function testDoesntPublishWhenLinkIdenticalToResourceUrl()
    {
        $resource = new CrawledResource(
            $url = Url::of('http://example.com/'),
            MediaType::null(),
            Map::of('string', Attribute::class)
                (
                    'links',
                    new Attribute\Attribute(
                        'links',
                        Set::of(
                            Url::class,
                            $url->withFragment(Fragment::of('foo'))
                        )
                    )
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
                    'links',
                    new Attribute\Attribute(
                        'links',
                        Set::of(
                            Url::class,
                            $published = Url::of('http://example.com/foo')
                        )
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
            ->with($this->callback(static function(Link $message) use ($published, $expected) {
                return $message->resource() === $published &&
                    $message->reference() === $expected;
            }));

        $this->assertSame($expected, ($this->publisher)($resource, $server));
    }
}
