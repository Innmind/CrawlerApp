<?php
declare(strict_types = 1);

namespace Tests\Crawler\Publisher;

use Crawler\{
    Publisher\ImagesAwarePublisher,
    Publisher,
    Reference,
    AMQP\Message\Image
};
use Innmind\Crawler\{
    HttpResource as CrawledResource,
    HttpResource\Attribute
};
use Innmind\Url\{
    UrlInterface,
    Url
};
use Innmind\Filesystem\MediaType;
use Innmind\Stream\Readable;
use Innmind\Rest\Client\Identity;
use Innmind\AMQP\Producer;
use Innmind\Immutable\{
    Map,
    SetInterface,
    Set
};
use PHPUnit\Framework\TestCase;

class ImagesAwarePublisherTest extends TestCase
{
    private $publisher;
    private $inner;
    private $producer;

    public function setUp()
    {
        $this->publisher = new ImagesAwarePublisher(
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

    public function testDoesntPublishWhenNoImage()
    {
        $resource = new CrawledResource(
            $this->createMock(UrlInterface::class),
            $this->createMock(MediaType::class),
            new Map('string', Attribute::class),
            $this->createMock(Readable::class)
        );
        $server = $this->createMock(UrlInterface::class);
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
            Url::fromString('http://example.com/'),
            $this->createMock(MediaType::class),
            (new Map('string', Attribute::class))
                ->put(
                    'images',
                    new Attribute\Attribute(
                        'images',
                        (new Map(UrlInterface::class, 'string'))
                            ->put($published = Url::fromString('http://example.com/foo'), 'some desc')
                    )
                ),
            $this->createMock(Readable::class)
        );
        $server = Url::fromString('http://server.url/');
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
            ->method('__toString')
            ->willReturn('some identity');
        $this
            ->producer
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function(Image $message) use ($published, $expected) {
                return $message->resource() === $published &&
                    $message->reference() === $expected &&
                    $message->description() === 'some desc';
            }));

        $this->assertSame($expected, ($this->publisher)($resource, $server));
    }
}