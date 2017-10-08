<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Publisher;

use AppBundle\{
    Publisher\CanonicalAwarePublisher,
    Publisher,
    Reference
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
use Innmind\Immutable\Map;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use PHPUnit\Framework\TestCase;

class CanonicalAwarePublisherTest extends TestCase
{
    private $publisher;
    private $inner;
    private $producer;

    public function setUp()
    {
        $this->publisher = new CanonicalAwarePublisher(
            $this->inner = $this->createMock(Publisher::class),
            $this->producer = $this->createMock(ProducerInterface::class)
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
                    $this->createMock(UrlInterface::class)
                )
            );
        $this
            ->producer
            ->expects($this->never())
            ->method('publish');

        $this->assertSame($expected, ($this->publisher)($resource, $server));
    }

    public function testDoesntPublishWhenCanonicalIdenticalToResourceUrl()
    {
        $resource = new CrawledResource(
            $url = Url::fromString('http://example.com/'),
            $this->createMock(MediaType::class),
            (new Map('string', Attribute::class))
                ->put(
                    'canonical',
                    new Attribute\Attribute('canonical', $url)
                ),
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
            ->method('publish');

        $this->assertSame($expected, ($this->publisher)($resource, $server));
    }

    public function testPublish()
    {
        $resource = new CrawledResource(
            Url::fromString('http://example.com/'),
            $this->createMock(MediaType::class),
            (new Map('string', Attribute::class))
                ->put(
                    'canonical',
                    new Attribute\Attribute('canonical', Url::fromString('http://example.com/foo'))
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
            ->method('publish')
            ->with(json_encode([
                'resource' => 'http://example.com/foo',
                'origin' => 'some identity',
                'relationship' => 'canonical',
                'definition' => 'foo',
                'server' => 'http://server.url/',
            ]));

        $this->assertSame($expected, ($this->publisher)($resource, $server));
    }
}
