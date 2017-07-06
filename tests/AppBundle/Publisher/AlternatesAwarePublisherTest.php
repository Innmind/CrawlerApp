<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Publisher;

use AppBundle\{
    Publisher\AlternatesAwarePublisher,
    PublisherInterface,
    Reference
};
use Innmind\Crawler\{
    HttpResource as CrawledResource,
    HttpResource\AttributeInterface,
    HttpResource\Attribute,
    HttpResource\Alternates,
    HttpResource\Alternate
};
use Innmind\Url\{
    UrlInterface,
    Url
};
use Innmind\Filesystem\{
    MediaTypeInterface,
    StreamInterface
};
use Innmind\Rest\Client\IdentityInterface;
use Innmind\Immutable\{
    Map,
    SetInterface,
    Set
};
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use PHPUnit\Framework\TestCase;

class AlternatesAwarePublisherTest extends TestCase
{
    private $publisher;
    private $inner;
    private $producer;

    public function setUp()
    {
        $this->publisher = new AlternatesAwarePublisher(
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

    public function testDoesntPublishWhenNoAlternate()
    {
        $resource = new CrawledResource(
            $this->createMock(UrlInterface::class),
            $this->createMock(MediaTypeInterface::class),
            new Map('string', AttributeInterface::class),
            $this->createMock(StreamInterface::class)
        );
        $server = $this->createMock(UrlInterface::class);
        $this
            ->inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($resource, $server)
            ->willReturn(
                $expected = new Reference(
                    $this->createMock(IdentityInterface::class),
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

    public function testDoesntPublishWhenAlternateIdenticalToResourceUrl()
    {
        $resource = new CrawledResource(
            $url = Url::fromString('http://example.com/'),
            $this->createMock(MediaTypeInterface::class),
            (new Map('string', AttributeInterface::class))
                ->put(
                    'alternates',
                    new Alternates(
                        (new Map('string', AttributeInterface::class))
                            ->put(
                                'en',
                                new Alternate(
                                    'en',
                                    (new Set(UrlInterface::class))->add($url)
                                )
                            )
                    )
                ),
            $this->createMock(StreamInterface::class)
        );
        $server = $this->createMock(UrlInterface::class);
        $this
            ->inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($resource, $server)
            ->willReturn(
                $expected = new Reference(
                    $this->createMock(IdentityInterface::class),
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

    public function testDoesntPublishWhenNotCorrectAttributeInstance()
    {
        $resource = new CrawledResource(
            $url = Url::fromString('http://example.com/'),
            $this->createMock(MediaTypeInterface::class),
            (new Map('string', AttributeInterface::class))
                ->put(
                    'alternates',
                    $this->createMock(AttributeInterface::class)
                ),
            $this->createMock(StreamInterface::class)
        );
        $server = $this->createMock(UrlInterface::class);
        $this
            ->inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($resource, $server)
            ->willReturn(
                $expected = new Reference(
                    $this->createMock(IdentityInterface::class),
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
            $this->createMock(MediaTypeInterface::class),
            (new Map('string', AttributeInterface::class))
                ->put(
                    'alternates',
                    new Alternates(
                        (new Map('string', AttributeInterface::class))
                            ->put(
                                'en',
                                new Alternate(
                                    'en',
                                    (new Set(UrlInterface::class))
                                        ->add(Url::fromString('http://example.com/foo'))
                                )
                            )
                    )
                ),
            $this->createMock(StreamInterface::class)
        );
        $server = Url::fromString('http://server.url/');
        $this
            ->inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($resource, $server)
            ->willReturn(
                $expected = new Reference(
                    $identity = $this->createMock(IdentityInterface::class),
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
                'relationship' => 'alternate',
                'attributes' => [
                    'language' => 'en',
                ],
                'definition' => 'foo',
                'server' => 'http://server.url/',
            ]));

        $this->assertSame($expected, ($this->publisher)($resource, $server));
    }
}
