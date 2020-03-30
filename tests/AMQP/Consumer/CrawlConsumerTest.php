<?php
declare(strict_types = 1);

namespace Tests\Crawler\AMQP\Consumer;

use Crawler\{
    AMQP\Consumer\CrawlConsumer,
    Publisher,
    Linker,
    Reference,
    Exception\UrlCannotBeCrawled,
    Exception\ResourceCannotBePublished,
    Exception\CantLinkResourceAcrossServers,
    Exception\ResponseTooHeavy,
};
use Innmind\Crawler\{
    Crawler,
    HttpResource as CrawledResource,
    HttpResource\Attribute,
};
use Innmind\Url\Url;
use Innmind\MediaType\MediaType;
use Innmind\Stream\Readable;
use Innmind\Rest\Client\Identity;
use Innmind\HttpTransport\Exception\{
    ConnectionFailed,
    ClientError,
    ServerError,
};
use Innmind\Http\Message\{
    Request,
    Response,
    StatusCode,
};
use Innmind\AMQP\{
    Model\Basic\Message\Generic,
    Model\Basic\Message\Locked,
    Model\Basic\Message\ContentType,
    Exception\Requeue,
};
use Innmind\Immutable\{
    Map,
    Str,
};
use PHPUnit\Framework\TestCase;

class CrawlConsumerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertTrue(
            is_callable(new CrawlConsumer(
                $this->createMock(Crawler::class),
                $this->createMock(Publisher::class),
                $this->createMock(Linker::class),
                'ua'
            ))
        );
    }

    public function testInvokation()
    {
        $consume = new CrawlConsumer(
            $crawler = $this->createMock(Crawler::class),
            $publisher = $this->createMock(Publisher::class),
            $linker = $this->createMock(Linker::class),
            'ua'
        );
        $message = new Locked(
            (new Generic(Str::of(json_encode([
                'resource' => 'foo',
                'origin' => 'origin',
                'definition' => 'definition',
                'server' => 'server',
            ]))))
                ->withContentType(new ContentType('application', 'json'))
        );
        $crawler
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function(Request $request): bool {
                return $request->url()->toString() === 'foo' &&
                    $request->method()->toString() === 'GET' &&
                    $request->protocolVersion()->toString() === '2.0' &&
                    $request->headers()->contains('User-Agent') &&
                    $request->headers()->get('User-Agent')->toString() === 'User-Agent: ua';
            }))
            ->willReturn($resource = new CrawledResource(
                Url::of('example.com'),
                MediaType::null(),
                Map::of('string', Attribute::class),
                $this->createMock(Readable::class)
            ));
        $publisher
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $resource,
                $this->callback(static function(Url $url): bool {
                    return $url->toString() === 'server';
                })
            )
            ->willReturn(new Reference(
                $this->createMock(Identity::class),
                'definition',
                Url::of('example.com')
            ));
        $linker
            ->expects($this->never())
            ->method('__invoke');

        $this->assertNull($consume($message));
    }

    public function testInvokeWhenCantConnectToHostOnCrawl()
    {
        $consume = new CrawlConsumer(
            $crawler = $this->createMock(Crawler::class),
            $publisher = $this->createMock(Publisher::class),
            $linker = $this->createMock(Linker::class),
            'ua'
        );
        $message = new Locked(
            (new Generic(Str::of(json_encode([
                'resource' => 'foo',
                'origin' => 'origin',
                'definition' => 'definition',
                'server' => 'server',
            ]))))
                ->withContentType(new ContentType('application', 'json'))
        );
        $crawler
            ->expects($this->once())
            ->method('__invoke')
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

        $this->assertNull($consume($message));
    }

    public function testInvokeWhenClientErrorOnCrawl()
    {
        $consume = new CrawlConsumer(
            $crawler = $this->createMock(Crawler::class),
            $publisher = $this->createMock(Publisher::class),
            $linker = $this->createMock(Linker::class),
            'ua'
        );
        $message = new Locked(
            (new Generic(Str::of(json_encode([
                'resource' => 'foo',
                'origin' => 'origin',
                'definition' => 'definition',
                'server' => 'server',
            ]))))
                ->withContentType(new ContentType('application', 'json'))
        );
        $crawler
            ->expects($this->once())
            ->method('__invoke')
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

        $this->assertNull($consume($message));
    }

    public function testInvokeWhenServerErrorOnCrawl()
    {
        $consume = new CrawlConsumer(
            $crawler = $this->createMock(Crawler::class),
            $publisher = $this->createMock(Publisher::class),
            $linker = $this->createMock(Linker::class),
            'ua'
        );
        $message = new Locked(
            (new Generic(Str::of(json_encode([
                'resource' => 'foo',
                'origin' => 'origin',
                'definition' => 'definition',
                'server' => 'server',
            ]))))
                ->withContentType(new ContentType('application', 'json'))
        );
        $crawler
            ->expects($this->once())
            ->method('__invoke')
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

        $this->expectException(Requeue::class);

        $consume($message);
    }

    public function testInvokeWhenUrlCannotBeCrawled()
    {
        $consume = new CrawlConsumer(
            $crawler = $this->createMock(Crawler::class),
            $publisher = $this->createMock(Publisher::class),
            $linker = $this->createMock(Linker::class),
            'ua'
        );
        $message = new Locked(
            (new Generic(Str::of(json_encode([
                'resource' => 'foo',
                'origin' => 'origin',
                'definition' => 'definition',
                'server' => 'server',
            ]))))
                ->withContentType(new ContentType('application', 'json'))
        );
        $crawler
            ->expects($this->once())
            ->method('__invoke')
            ->will($this->throwException(
                new UrlCannotBeCrawled(
                    Url::of('example.com')
                )
            ));
        $publisher
            ->expects($this->never())
            ->method('__invoke');
        $linker
            ->expects($this->never())
            ->method('__invoke');

        $this->assertNull($consume($message));
    }

    public function testInvokeWhenResponseTooHeavy()
    {
        $consume = new CrawlConsumer(
            $crawler = $this->createMock(Crawler::class),
            $publisher = $this->createMock(Publisher::class),
            $linker = $this->createMock(Linker::class),
            'ua'
        );
        $message = new Locked(
            (new Generic(Str::of(json_encode([
                'resource' => 'foo',
                'origin' => 'origin',
                'definition' => 'definition',
                'server' => 'server',
            ]))))
                ->withContentType(new ContentType('application', 'json'))
        );
        $crawler
            ->expects($this->once())
            ->method('__invoke')
            ->will($this->throwException(new ResponseTooHeavy));
        $publisher
            ->expects($this->never())
            ->method('__invoke');
        $linker
            ->expects($this->never())
            ->method('__invoke');

        $this->assertNull($consume($message));
    }

    public function testLink()
    {
        $consume = new CrawlConsumer(
            $crawler = $this->createMock(Crawler::class),
            $publisher = $this->createMock(Publisher::class),
            $linker = $this->createMock(Linker::class),
            'ua'
        );
        $message = new Locked(
            (new Generic(Str::of(json_encode([
                'resource' => 'foo',
                'origin' => 'origin',
                'relationship' => 'referrer',
                'definition' => 'definition',
                'server' => 'server',
                'attributes' => ['foo', 'bar'],
            ]))))
                ->withContentType(new ContentType('application', 'json'))
        );
        $crawler
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function(Request $request): bool {
                return $request->url()->toString() === 'foo' &&
                    $request->method()->toString() === 'GET' &&
                    $request->headers()->contains('User-Agent') &&
                    $request->headers()->get('User-Agent')->toString() === 'User-Agent: ua';
            }))
            ->willReturn($resource = new CrawledResource(
                Url::of('example.com'),
                MediaType::null(),
                Map::of('string', Attribute::class),
                $this->createMock(Readable::class)
            ));
        $publisher
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $resource,
                $this->callback(static function(Url $url): bool {
                    return $url->toString() === 'server';
                })
            )
            ->willReturn($reference = new Reference(
                $this->createMock(Identity::class),
                'definition',
                Url::of('example.com')
            ));
        $linker
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $reference,
                $this->callback(function(Reference $reference): bool {
                    return $reference->identity()->toString() === 'origin' &&
                        $reference->definition() === 'definition' &&
                        $reference->server()->toString() === 'server';
                }),
                'referrer',
                ['foo', 'bar']
            );

        $this->assertNull($consume($message));
    }

    public function testPublishEvenOnConflictDetected()
    {
        $consume = new CrawlConsumer(
            $crawler = $this->createMock(Crawler::class),
            $publisher = $this->createMock(Publisher::class),
            $linker = $this->createMock(Linker::class),
            'ua'
        );
        $message = new Locked(
            (new Generic(Str::of(json_encode([
                'resource' => 'foo',
                'origin' => 'origin',
                'relationship' => 'referrer',
                'definition' => 'definition',
                'server' => 'server',
                'attributes' => ['foo', 'bar'],
            ]))))
                ->withContentType(new ContentType('application', 'json'))
        );
        $crawler
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function(Request $request): bool {
                return $request->url()->toString() === 'foo' &&
                    $request->method()->toString() === 'GET' &&
                    $request->headers()->contains('User-Agent') &&
                    $request->headers()->get('User-Agent')->toString() === 'User-Agent: ua';
            }))
            ->willReturn($resource = new CrawledResource(
                Url::of('example.com'),
                MediaType::null(),
                Map::of('string', Attribute::class),
                $this->createMock(Readable::class)
            ));
        $publisher
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $resource,
                $this->callback(static function(Url $url): bool {
                    return $url->toString() === 'server';
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

        $this->assertNull($consume($message));
    }

    public function testThrowOnClientErrorOnPublish()
    {
        $consume = new CrawlConsumer(
            $crawler = $this->createMock(Crawler::class),
            $publisher = $this->createMock(Publisher::class),
            $linker = $this->createMock(Linker::class),
            'ua'
        );
        $message = new Locked(
            (new Generic(Str::of(json_encode([
                'resource' => 'foo',
                'origin' => 'origin',
                'relationship' => 'referrer',
                'definition' => 'definition',
                'server' => 'server',
                'attributes' => ['foo', 'bar'],
            ]))))
                ->withContentType(new ContentType('application', 'json'))
        );
        $crawler
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function(Request $request): bool {
                return $request->url()->toString() === 'foo' &&
                    $request->method()->toString() === 'GET' &&
                    $request->headers()->contains('User-Agent') &&
                    $request->headers()->get('User-Agent')->toString() === 'User-Agent: ua';
            }))
            ->willReturn($resource = new CrawledResource(
                Url::of('example.com'),
                MediaType::null(),
                Map::of('string', Attribute::class),
                $this->createMock(Readable::class)
            ));
        $publisher
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $resource,
                $this->callback(static function(Url $url): bool {
                    return $url->toString() === 'server';
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

        $this->expectException(ClientError::class);

        $consume($message);
    }

    public function testInvokeWhenFailsToPublish()
    {
        $consume = new CrawlConsumer(
            $crawler = $this->createMock(Crawler::class),
            $publisher = $this->createMock(Publisher::class),
            $linker = $this->createMock(Linker::class),
            'ua'
        );
        $message = new Locked(
            (new Generic(Str::of(json_encode([
                'resource' => 'foo',
                'origin' => 'origin',
                'relationship' => 'referrer',
                'definition' => 'definition',
                'server' => 'server',
                'attributes' => ['foo', 'bar'],
            ]))))
                ->withContentType(new ContentType('application', 'json'))
        );
        $crawler
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function(Request $request): bool {
                return $request->url()->toString() === 'foo' &&
                    $request->method()->toString() === 'GET' &&
                    $request->headers()->contains('User-Agent') &&
                    $request->headers()->get('User-Agent')->toString() === 'User-Agent: ua';
            }))
            ->willReturn($resource = new CrawledResource(
                Url::of('example.com'),
                MediaType::null(),
                Map::of('string', Attribute::class),
                $this->createMock(Readable::class)
            ));
        $publisher
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $resource,
                $this->callback(static function(Url $url): bool {
                    return $url->toString() === 'server';
                })
            )
            ->will($this->throwException(
                new ResourceCannotBePublished($resource)
            ));
        $linker
            ->expects($this->never())
            ->method('__invoke');

        $this->assertNull($consume($message));
    }

    public function testInvokeWhenTryingToLinkAcrossServers()
    {
        $consume = new CrawlConsumer(
            $crawler = $this->createMock(Crawler::class),
            $publisher = $this->createMock(Publisher::class),
            $linker = $this->createMock(Linker::class),
            'ua'
        );
        $message = new Locked(
            (new Generic(Str::of(json_encode([
                'resource' => 'foo',
                'origin' => 'origin',
                'relationship' => 'referrer',
                'definition' => 'definition',
                'server' => 'server',
                'attributes' => ['foo', 'bar'],
            ]))))
                ->withContentType(new ContentType('application', 'json'))
        );
        $crawler
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function(Request $request): bool {
                return $request->url()->toString() === 'foo' &&
                    $request->method()->toString() === 'GET' &&
                    $request->headers()->contains('User-Agent') &&
                    $request->headers()->get('User-Agent')->toString() === 'User-Agent: ua';
            }))
            ->willReturn($resource = new CrawledResource(
                Url::of('example.com'),
                MediaType::null(),
                Map::of('string', Attribute::class),
                $this->createMock(Readable::class)
            ));
        $publisher
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $resource,
                $this->callback(static function(Url $url): bool {
                    return $url->toString() === 'server';
                })
            )
            ->willReturn($reference = new Reference(
                $this->createMock(Identity::class),
                'definition',
                Url::of('example.com')
            ));
        $linker
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $reference,
                $this->callback(function(Reference $reference): bool {
                    return $reference->identity()->toString() === 'origin' &&
                        $reference->definition() === 'definition' &&
                        $reference->server()->toString() === 'server';
                }),
                'referrer',
                ['foo', 'bar']
            )
            ->will($this->throwException(
                new CantLinkResourceAcrossServers($reference, $reference)
            ));

        $this->assertNull($consume($message));
    }

    public function testRequeueWhenServerFailDuringPublish()
    {
        $consume = new CrawlConsumer(
            $crawler = $this->createMock(Crawler::class),
            $publisher = $this->createMock(Publisher::class),
            $linker = $this->createMock(Linker::class),
            'ua'
        );
        $message = new Locked(
            (new Generic(Str::of(json_encode([
                'resource' => 'foo',
                'origin' => 'origin',
                'relationship' => 'referrer',
                'definition' => 'definition',
                'server' => 'server',
                'attributes' => ['foo', 'bar'],
            ]))))
                ->withContentType(new ContentType('application', 'json'))
        );
        $crawler
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function(Request $request): bool {
                return $request->url()->toString() === 'foo' &&
                    $request->method()->toString() === 'GET' &&
                    $request->headers()->contains('User-Agent') &&
                    $request->headers()->get('User-Agent')->toString() === 'User-Agent: ua';
            }))
            ->willReturn($resource = new CrawledResource(
                Url::of('example.com'),
                MediaType::null(),
                Map::of('string', Attribute::class),
                $this->createMock(Readable::class)
            ));
        $publisher
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $resource,
                $this->callback(static function(Url $url): bool {
                    return $url->toString() === 'server';
                })
            )
            ->willReturn($reference = new Reference(
                $this->createMock(Identity::class),
                'definition',
                Url::of('example.com')
            ));
        $linker
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $reference,
                $this->callback(function(Reference $reference): bool {
                    return $reference->identity()->toString() === 'origin' &&
                        $reference->definition() === 'definition' &&
                        $reference->server()->toString() === 'server';
                }),
                'referrer',
                ['foo', 'bar']
            )
            ->will($this->throwException(
                new ServerError(
                    $this->createMock(Request::class),
                    $this->createMock(Response::class)
                )
            ));

        $this->expectException(Requeue::class);

        $consume($message);
    }
}
