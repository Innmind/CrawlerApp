<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Publisher;

use AppBundle\{
    Publisher\ImagesAwarePublisher,
    PublisherInterface,
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
use Innmind\Immutable\{
    Map,
    SetInterface,
    Set
};
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use PHPUnit\Framework\TestCase;

class ImagesAwarePublisherTest extends TestCase
{
    private $publisher;
    private $inner;
    private $producer;

    public function setUp()
    {
        $this->publisher = new ImagesAwarePublisher(
            $this->inner = $this->createMock(PublisherInterface::class),
            $this->producer = $this->createMock(ProducerInterface::class)
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            PublisherInterface::class,
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
                    'images',
                    new Attribute\Attribute(
                        'images',
                        (new Map(UrlInterface::class, 'string'))
                            ->put(Url::fromString('http://example.com/foo'), 'some desc')
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
            ->method('publish')
            ->with(serialize([
                'resource' => 'http://example.com/foo',
                'origin' => 'some identity',
                'relationship' => 'referrer',
                'attributes' => [
                    'description' => 'some desc',
                ],
                'definition' => 'foo',
                'server' => 'http://server.url/',
            ]));

        $this->assertSame($expected, ($this->publisher)($resource, $server));
    }
}
