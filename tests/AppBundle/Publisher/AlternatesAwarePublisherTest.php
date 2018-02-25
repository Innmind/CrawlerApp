<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Publisher;

use AppBundle\{
    Publisher\AlternatesAwarePublisher,
    Publisher,
    Reference,
    AMQP\Message\Alternate as Message
};
use Innmind\Crawler\{
    HttpResource as CrawledResource,
    HttpResource\Attribute,
    HttpResource\Alternates,
    HttpResource\Alternate
};
use Innmind\Url\{
    UrlInterface,
    Url,
    Fragment
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

class AlternatesAwarePublisherTest extends TestCase
{
    private $publisher;
    private $inner;
    private $producer;

    public function setUp()
    {
        $this->publisher = new AlternatesAwarePublisher(
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

    public function testDoesntPublishWhenNoAlternate()
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

    public function testDoesntPublishWhenAlternateIdenticalToResourceUrl()
    {
        $resource = new CrawledResource(
            $url = Url::fromString('http://example.com/'),
            $this->createMock(MediaType::class),
            (new Map('string', Attribute::class))
                ->put(
                    'alternates',
                    new Alternates(
                        (new Map('string', Attribute::class))
                            ->put(
                                'en',
                                new Alternate(
                                    'en',
                                    (new Set(UrlInterface::class))
                                        ->add($url->withFragment(new Fragment('foo')))
                                )
                            )
                    )
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
            ->method('__invoke');

        $this->assertSame($expected, ($this->publisher)($resource, $server));
    }

    public function testDoesntPublishWhenNotCorrectAttributeInstance()
    {
        $resource = new CrawledResource(
            $url = Url::fromString('http://example.com/'),
            $this->createMock(MediaType::class),
            (new Map('string', Attribute::class))
                ->put(
                    'alternates',
                    $this->createMock(Attribute::class)
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
                    'alternates',
                    new Alternates(
                        (new Map('string', Attribute::class))
                            ->put(
                                'en',
                                new Alternate(
                                    'en',
                                    (new Set(UrlInterface::class))
                                        ->add($published = Url::fromString('http://example.com/foo'))
                                )
                            )
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
            ->with($this->callback(function(Message $message) use ($published, $expected) {
                return $message->resource() === $published &&
                    $message->reference() === $expected &&
                    $message->language() === 'en';
            }));

        $this->assertSame($expected, ($this->publisher)($resource, $server));
    }
}
