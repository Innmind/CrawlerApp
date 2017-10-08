<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Consumer;

use AppBundle\{
    Consumer\CrawlConsumer,
    Publisher,
    Linker,
    Reference,
    Exception\UrlCannotBeCrawled,
    Exception\ResourceCannotBePublished,
    Exception\CantLinkResourceAcrossServers
};
use Innmind\Crawler\{
    Crawler,
    HttpResource as CrawledResource,
    HttpResource\Attribute
};
use Innmind\Url\UrlInterface;
use Innmind\Filesystem\MediaType;
use Innmind\Stream\Readable;
use Innmind\Rest\Client\Identity;
use Innmind\HttpTransport\Exception\{
    ConnectionFailed,
    ClientError,
    ServerError
};
use Innmind\Http\Message\{
    Request,
    Response,
    StatusCode\StatusCode
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
                $this->createMock(Crawler::class),
                $this->createMock(Publisher::class),
                $this->createMock(Linker::class),
                'ua'
            )
        );
    }

    public function testExecute()
    {
        $consumer = new CrawlConsumer(
            $crawler = $this->createMock(Crawler::class),
            $publisher = $this->createMock(Publisher::class),
            $linker = $this->createMock(Linker::class),
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
            ->with($this->callback(static function(Request $request): bool {
                return (string) $request->url() === 'foo' &&
                    (string) $request->method() === 'GET' &&
                    (string) $request->protocolVersion() === '2.0' &&
                    $request->headers()->has('User-Agent') &&
                    (string) $request->headers()->get('User-Agent') === 'User-Agent : ua';
            }))
            ->willReturn($resource = new CrawledResource(
                $this->createMock(UrlInterface::class),
                $this->createMock(MediaType::class),
                new Map('string', Attribute::class),
                $this->createMock(Readable::class)
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
                $this->createMock(Identity::class),
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
            $crawler = $this->createMock(Crawler::class),
            $publisher = $this->createMock(Publisher::class),
            $linker = $this->createMock(Linker::class),
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
                new ConnectionFailed(
                    $this->createMock(Request::class),
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
            $crawler = $this->createMock(Crawler::class),
            $publisher = $this->createMock(Publisher::class),
            $linker = $this->createMock(Linker::class),
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
                new ClientError(
                    $this->createMock(Request::class),
                    $this->createMock(Response::class)
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
            $crawler = $this->createMock(Crawler::class),
            $publisher = $this->createMock(Publisher::class),
            $linker = $this->createMock(Linker::class),
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
                new ServerError(
                    $this->createMock(Request::class),
                    $this->createMock(Response::class)
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
            $crawler = $this->createMock(Crawler::class),
            $publisher = $this->createMock(Publisher::class),
            $linker = $this->createMock(Linker::class),
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
                new UrlCannotBeCrawled(
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
            $crawler = $this->createMock(Crawler::class),
            $publisher = $this->createMock(Publisher::class),
            $linker = $this->createMock(Linker::class),
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
            ->with($this->callback(static function(Request $request): bool {
                return (string) $request->url() === 'foo' &&
                    (string) $request->method() === 'GET' &&
                    $request->headers()->has('User-Agent') &&
                    (string) $request->headers()->get('User-Agent') === 'User-Agent : ua';
            }))
            ->willReturn($resource = new CrawledResource(
                $this->createMock(UrlInterface::class),
                $this->createMock(MediaType::class),
                new Map('string', Attribute::class),
                $this->createMock(Readable::class)
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
                $this->createMock(Identity::class),
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
            $crawler = $this->createMock(Crawler::class),
            $publisher = $this->createMock(Publisher::class),
            $linker = $this->createMock(Linker::class),
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
            ->with($this->callback(static function(Request $request): bool {
                return (string) $request->url() === 'foo' &&
                    (string) $request->method() === 'GET' &&
                    $request->headers()->has('User-Agent') &&
                    (string) $request->headers()->get('User-Agent') === 'User-Agent : ua';
            }))
            ->willReturn($resource = new CrawledResource(
                $this->createMock(UrlInterface::class),
                $this->createMock(MediaType::class),
                new Map('string', Attribute::class),
                $this->createMock(Readable::class)
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
                new ClientError(
                    $this->createMock(Request::class),
                    $response = $this->createMock(Response::class)
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
     * @expectedException Innmind\HttpTransport\Exception\ClientError
     */
    public function testThrowOnClientErrorOnPublish()
    {
        $consumer = new CrawlConsumer(
            $crawler = $this->createMock(Crawler::class),
            $publisher = $this->createMock(Publisher::class),
            $linker = $this->createMock(Linker::class),
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
            ->with($this->callback(static function(Request $request): bool {
                return (string) $request->url() === 'foo' &&
                    (string) $request->method() === 'GET' &&
                    $request->headers()->has('User-Agent') &&
                    (string) $request->headers()->get('User-Agent') === 'User-Agent : ua';
            }))
            ->willReturn($resource = new CrawledResource(
                $this->createMock(UrlInterface::class),
                $this->createMock(MediaType::class),
                new Map('string', Attribute::class),
                $this->createMock(Readable::class)
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
                new ClientError(
                    $this->createMock(Request::class),
                    $response = $this->createMock(Response::class)
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
            $crawler = $this->createMock(Crawler::class),
            $publisher = $this->createMock(Publisher::class),
            $linker = $this->createMock(Linker::class),
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
            ->with($this->callback(static function(Request $request): bool {
                return (string) $request->url() === 'foo' &&
                    (string) $request->method() === 'GET' &&
                    $request->headers()->has('User-Agent') &&
                    (string) $request->headers()->get('User-Agent') === 'User-Agent : ua';
            }))
            ->willReturn($resource = new CrawledResource(
                $this->createMock(UrlInterface::class),
                $this->createMock(MediaType::class),
                new Map('string', Attribute::class),
                $this->createMock(Readable::class)
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
                new ResourceCannotBePublished($resource)
            ));
        $linker
            ->expects($this->never())
            ->method('__invoke');

        $this->assertTrue($consumer->execute($message));
    }

    public function testExecuteWhenTryingToLinkAcrossServers()
    {
        $consumer = new CrawlConsumer(
            $crawler = $this->createMock(Crawler::class),
            $publisher = $this->createMock(Publisher::class),
            $linker = $this->createMock(Linker::class),
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
            ->with($this->callback(static function(Request $request): bool {
                return (string) $request->url() === 'foo' &&
                    (string) $request->method() === 'GET' &&
                    $request->headers()->has('User-Agent') &&
                    (string) $request->headers()->get('User-Agent') === 'User-Agent : ua';
            }))
            ->willReturn($resource = new CrawledResource(
                $this->createMock(UrlInterface::class),
                $this->createMock(MediaType::class),
                new Map('string', Attribute::class),
                $this->createMock(Readable::class)
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
                $this->createMock(Identity::class),
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
                new CantLinkResourceAcrossServers($reference, $reference)
            ));

        $this->assertTrue($consumer->execute($message));
    }
}
