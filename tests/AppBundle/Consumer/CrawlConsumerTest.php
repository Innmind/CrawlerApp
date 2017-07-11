<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Consumer;

use AppBundle\{
    Consumer\CrawlConsumer,
    PublisherInterface,
    LinkerInterface,
    Reference,
    Exception\UrlCannotBeCrawledException,
    Exception\ResourceCannotBePublishedException,
    Exception\CantLinkResourceAcrossServersException
};
use Innmind\Crawler\{
    CrawlerInterface,
    HttpResource as CrawledResource,
    HttpResource\AttributeInterface
};
use Innmind\Url\UrlInterface;
use Innmind\Filesystem\{
    MediaTypeInterface,
    StreamInterface
};
use Innmind\Rest\Client\IdentityInterface;
use Innmind\HttpTransport\Exception\{
    ConnectException,
    ClientErrorException,
    ServerErrorException
};
use Innmind\Http\Message\{
    RequestInterface,
    ResponseInterface,
    StatusCode
};
use Innmind\Immutable\Map;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;

class CrawlConsumerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            ConsumerInterface::class,
            new CrawlConsumer(
                $this->createMock(CrawlerInterface::class),
                $this->createMock(PublisherInterface::class),
                $this->createMock(LinkerInterface::class),
                'ua'
            )
        );
    }

    public function testExecute()
    {
        $consumer = new CrawlConsumer(
            $crawler = $this->createMock(CrawlerInterface::class),
            $publisher = $this->createMock(PublisherInterface::class),
            $linker = $this->createMock(LinkerInterface::class),
            'ua'
        );
        $message = new AMQPMessage(serialize([
            'resource' => 'foo',
            'origin' => 'origin',
            'definition' => 'definition',
            'server' => 'server',
        ]));
        $crawler
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function(RequestInterface $request): bool {
                return (string) $request->url() === 'foo' &&
                    (string) $request->method() === 'GET' &&
                    $request->headers()->has('User-Agent') &&
                    (string) $request->headers()->get('User-Agent') === 'User-Agent : ua';
            }))
            ->willReturn($resource = new CrawledResource(
                $this->createMock(UrlInterface::class),
                $this->createMock(MediaTypeInterface::class),
                new Map('string', AttributeInterface::class),
                $this->createMock(StreamInterface::class)
            ));
        $publisher
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $resource,
                $this->callback(static function(UrlInterface $url): bool {
                    return (string) $url === 'server';
                })
            )
            ->willReturn(new Reference(
                $this->createMock(IdentityInterface::class),
                'definition',
                $this->createMock(UrlInterface::class)
            ));
        $linker
            ->expects($this->never())
            ->method('__invoke');

        $this->assertTrue($consumer->execute($message));
    }

    public function testExecuteWhenCantConnectToHostOnCrawl()
    {
        $consumer = new CrawlConsumer(
            $crawler = $this->createMock(CrawlerInterface::class),
            $publisher = $this->createMock(PublisherInterface::class),
            $linker = $this->createMock(LinkerInterface::class),
            'ua'
        );
        $message = new AMQPMessage(serialize([
            'resource' => 'foo',
            'origin' => 'origin',
            'definition' => 'definition',
            'server' => 'server',
        ]));
        $crawler
            ->expects($this->once())
            ->method('execute')
            ->will($this->throwException(
                new ConnectException(
                    $this->createMock(RequestInterface::class),
                    new \Exception
                )
            ));
        $publisher
            ->expects($this->never())
            ->method('__invoke');
        $linker
            ->expects($this->never())
            ->method('__invoke');

        $this->assertTrue($consumer->execute($message));
    }

    public function testExecuteWhenClientErrorOnCrawl()
    {
        $consumer = new CrawlConsumer(
            $crawler = $this->createMock(CrawlerInterface::class),
            $publisher = $this->createMock(PublisherInterface::class),
            $linker = $this->createMock(LinkerInterface::class),
            'ua'
        );
        $message = new AMQPMessage(serialize([
            'resource' => 'foo',
            'origin' => 'origin',
            'definition' => 'definition',
            'server' => 'server',
        ]));
        $crawler
            ->expects($this->once())
            ->method('execute')
            ->will($this->throwException(
                new ClientErrorException(
                    $this->createMock(RequestInterface::class),
                    $this->createMock(ResponseInterface::class)
                )
            ));
        $publisher
            ->expects($this->never())
            ->method('__invoke');
        $linker
            ->expects($this->never())
            ->method('__invoke');

        $this->assertTrue($consumer->execute($message));
    }

    public function testExecuteWhenServerErrorOnCrawl()
    {
        $consumer = new CrawlConsumer(
            $crawler = $this->createMock(CrawlerInterface::class),
            $publisher = $this->createMock(PublisherInterface::class),
            $linker = $this->createMock(LinkerInterface::class),
            'ua'
        );
        $message = new AMQPMessage(serialize([
            'resource' => 'foo',
            'origin' => 'origin',
            'definition' => 'definition',
            'server' => 'server',
        ]));
        $crawler
            ->expects($this->once())
            ->method('execute')
            ->will($this->throwException(
                new ServerErrorException(
                    $this->createMock(RequestInterface::class),
                    $this->createMock(ResponseInterface::class)
                )
            ));
        $publisher
            ->expects($this->never())
            ->method('__invoke');
        $linker
            ->expects($this->never())
            ->method('__invoke');

        $this->assertFalse($consumer->execute($message));
    }

    public function testExecuteWhenUrlCannotBeCrawled()
    {
        $consumer = new CrawlConsumer(
            $crawler = $this->createMock(CrawlerInterface::class),
            $publisher = $this->createMock(PublisherInterface::class),
            $linker = $this->createMock(LinkerInterface::class),
            'ua'
        );
        $message = new AMQPMessage(serialize([
            'resource' => 'foo',
            'origin' => 'origin',
            'definition' => 'definition',
            'server' => 'server',
        ]));
        $crawler
            ->expects($this->once())
            ->method('execute')
            ->will($this->throwException(
                new UrlCannotBeCrawledException(
                    $this->createMock(UrlInterface::class)
                )
            ));
        $publisher
            ->expects($this->never())
            ->method('__invoke');
        $linker
            ->expects($this->never())
            ->method('__invoke');

        $this->assertTrue($consumer->execute($message));
    }

    public function testLink()
    {
        $consumer = new CrawlConsumer(
            $crawler = $this->createMock(CrawlerInterface::class),
            $publisher = $this->createMock(PublisherInterface::class),
            $linker = $this->createMock(LinkerInterface::class),
            'ua'
        );
        $message = new AMQPMessage(serialize([
            'resource' => 'foo',
            'origin' => 'origin',
            'relationship' => 'referrer',
            'definition' => 'definition',
            'server' => 'server',
            'attributes' => ['foo', 'bar'],
        ]));
        $crawler
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function(RequestInterface $request): bool {
                return (string) $request->url() === 'foo' &&
                    (string) $request->method() === 'GET' &&
                    $request->headers()->has('User-Agent') &&
                    (string) $request->headers()->get('User-Agent') === 'User-Agent : ua';
            }))
            ->willReturn($resource = new CrawledResource(
                $this->createMock(UrlInterface::class),
                $this->createMock(MediaTypeInterface::class),
                new Map('string', AttributeInterface::class),
                $this->createMock(StreamInterface::class)
            ));
        $publisher
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $resource,
                $this->callback(static function(UrlInterface $url): bool {
                    return (string) $url === 'server';
                })
            )
            ->willReturn($reference = new Reference(
                $this->createMock(IdentityInterface::class),
                'definition',
                $this->createMock(UrlInterface::class)
            ));
        $linker
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $reference,
                $this->callback(function(Reference $reference): bool {
                    return (string) $reference->identity() === 'origin' &&
                        $reference->definition() === 'definition' &&
                        (string) $reference->server() === 'server';
                }),
                'referrer',
                ['foo', 'bar']
            );

        $this->assertTrue($consumer->execute($message));
    }

    public function testPublishEvenOnConflictDetected()
    {
        $consumer = new CrawlConsumer(
            $crawler = $this->createMock(CrawlerInterface::class),
            $publisher = $this->createMock(PublisherInterface::class),
            $linker = $this->createMock(LinkerInterface::class),
            'ua'
        );
        $message = new AMQPMessage(serialize([
            'resource' => 'foo',
            'origin' => 'origin',
            'relationship' => 'referrer',
            'definition' => 'definition',
            'server' => 'server',
            'attributes' => ['foo', 'bar'],
        ]));
        $crawler
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function(RequestInterface $request): bool {
                return (string) $request->url() === 'foo' &&
                    (string) $request->method() === 'GET' &&
                    $request->headers()->has('User-Agent') &&
                    (string) $request->headers()->get('User-Agent') === 'User-Agent : ua';
            }))
            ->willReturn($resource = new CrawledResource(
                $this->createMock(UrlInterface::class),
                $this->createMock(MediaTypeInterface::class),
                new Map('string', AttributeInterface::class),
                $this->createMock(StreamInterface::class)
            ));
        $publisher
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $resource,
                $this->callback(static function(UrlInterface $url): bool {
                    return (string) $url === 'server';
                })
            )
            ->will($this->throwException(
                new ClientErrorException(
                    $this->createMock(RequestInterface::class),
                    $response = $this->createMock(ResponseInterface::class)
                )
            ));
        $response
            ->expects($this->once())
            ->method('statusCode')
            ->willReturn(new StatusCode(409));
        $linker
            ->expects($this->never())
            ->method('__invoke');

        $this->assertTrue($consumer->execute($message));
    }

    /**
     * @expectedException Innmind\HttpTransport\Exception\ClientErrorException
     */
    public function testThrowOnClientErrorOnPublish()
    {
        $consumer = new CrawlConsumer(
            $crawler = $this->createMock(CrawlerInterface::class),
            $publisher = $this->createMock(PublisherInterface::class),
            $linker = $this->createMock(LinkerInterface::class),
            'ua'
        );
        $message = new AMQPMessage(serialize([
            'resource' => 'foo',
            'origin' => 'origin',
            'relationship' => 'referrer',
            'definition' => 'definition',
            'server' => 'server',
            'attributes' => ['foo', 'bar'],
        ]));
        $crawler
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function(RequestInterface $request): bool {
                return (string) $request->url() === 'foo' &&
                    (string) $request->method() === 'GET' &&
                    $request->headers()->has('User-Agent') &&
                    (string) $request->headers()->get('User-Agent') === 'User-Agent : ua';
            }))
            ->willReturn($resource = new CrawledResource(
                $this->createMock(UrlInterface::class),
                $this->createMock(MediaTypeInterface::class),
                new Map('string', AttributeInterface::class),
                $this->createMock(StreamInterface::class)
            ));
        $publisher
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $resource,
                $this->callback(static function(UrlInterface $url): bool {
                    return (string) $url === 'server';
                })
            )
            ->will($this->throwException(
                new ClientErrorException(
                    $this->createMock(RequestInterface::class),
                    $response = $this->createMock(ResponseInterface::class)
                )
            ));
        $response
            ->expects($this->once())
            ->method('statusCode')
            ->willReturn(new StatusCode(400));
        $linker
            ->expects($this->never())
            ->method('__invoke');

        $this->assertTrue($consumer->execute($message));
    }

    public function testExecuteWhenFailsToPublish()
    {
        $consumer = new CrawlConsumer(
            $crawler = $this->createMock(CrawlerInterface::class),
            $publisher = $this->createMock(PublisherInterface::class),
            $linker = $this->createMock(LinkerInterface::class),
            'ua'
        );
        $message = new AMQPMessage(serialize([
            'resource' => 'foo',
            'origin' => 'origin',
            'relationship' => 'referrer',
            'definition' => 'definition',
            'server' => 'server',
            'attributes' => ['foo', 'bar'],
        ]));
        $crawler
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function(RequestInterface $request): bool {
                return (string) $request->url() === 'foo' &&
                    (string) $request->method() === 'GET' &&
                    $request->headers()->has('User-Agent') &&
                    (string) $request->headers()->get('User-Agent') === 'User-Agent : ua';
            }))
            ->willReturn($resource = new CrawledResource(
                $this->createMock(UrlInterface::class),
                $this->createMock(MediaTypeInterface::class),
                new Map('string', AttributeInterface::class),
                $this->createMock(StreamInterface::class)
            ));
        $publisher
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $resource,
                $this->callback(static function(UrlInterface $url): bool {
                    return (string) $url === 'server';
                })
            )
            ->will($this->throwException(
                new ResourceCannotBePublishedException($resource)
            ));
        $linker
            ->expects($this->never())
            ->method('__invoke');

        $this->assertTrue($consumer->execute($message));
    }

    public function testExecuteWhenTryingToLinkAcrossServers()
    {
        $consumer = new CrawlConsumer(
            $crawler = $this->createMock(CrawlerInterface::class),
            $publisher = $this->createMock(PublisherInterface::class),
            $linker = $this->createMock(LinkerInterface::class),
            'ua'
        );
        $message = new AMQPMessage(serialize([
            'resource' => 'foo',
            'origin' => 'origin',
            'relationship' => 'referrer',
            'definition' => 'definition',
            'server' => 'server',
            'attributes' => ['foo', 'bar'],
        ]));
        $crawler
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function(RequestInterface $request): bool {
                return (string) $request->url() === 'foo' &&
                    (string) $request->method() === 'GET' &&
                    $request->headers()->has('User-Agent') &&
                    (string) $request->headers()->get('User-Agent') === 'User-Agent : ua';
            }))
            ->willReturn($resource = new CrawledResource(
                $this->createMock(UrlInterface::class),
                $this->createMock(MediaTypeInterface::class),
                new Map('string', AttributeInterface::class),
                $this->createMock(StreamInterface::class)
            ));
        $publisher
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $resource,
                $this->callback(static function(UrlInterface $url): bool {
                    return (string) $url === 'server';
                })
            )
            ->willReturn($reference = new Reference(
                $this->createMock(IdentityInterface::class),
                'definition',
                $this->createMock(UrlInterface::class)
            ));
        $linker
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $reference,
                $this->callback(function(Reference $reference): bool {
                    return (string) $reference->identity() === 'origin' &&
                        $reference->definition() === 'definition' &&
                        (string) $reference->server() === 'server';
                }),
                'referrer',
                ['foo', 'bar']
            )
            ->will($this->throwException(
                new CantLinkResourceAcrossServersException($reference, $reference)
            ));

        $this->assertTrue($consumer->execute($message));
    }
}
