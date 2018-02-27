<?php
declare(strict_types = 1);

namespace Tests\Crawler\Command;

use Crawler\{
    Command\Crawl,
    Publisher,
    Reference,
};
use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
};
use Innmind\Crawler\{
    Crawler,
    HttpResource,
    HttpResource\Attribute,
};
use Innmind\Url\UrlInterface;
use Innmind\Filesystem\MediaType;
use Innmind\Stream\Readable;
use Innmind\Rest\Client\Identity;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class CrawlTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Command::class,
            new Crawl(
                $this->createMock(Crawler::class),
                'foo',
                $this->createMock(Publisher::class)
            )
        );
    }

    public function testStringCast()
    {
        $command = new Crawl(
            $this->createMock(Crawler::class),
            'foo',
            $this->createMock(Publisher::class)
        );

        $expected = <<<USAGE
crawl url [publish]

Crawl the given url and will print all the attributes found

The "publish" argument is an optional url where to publish the crawled resource
USAGE;

        $this->assertSame($expected, (string) $command);
    }

    public function testCrawl()
    {
        $command = new Crawl(
            $crawler = $this->createMock(Crawler::class),
            'foo',
            $publisher = $this->createMock(Publisher::class)
        );
        $crawler
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($request): bool {
                return (string) $request->url() === 'http://example.com/' &&
                    (string) $request->method() === 'GET' &&
                    (string) $request->protocolVersion() === '2.0' &&
                    (string) $request->headers()->get('user-agent') === 'User-Agent : foo';
            }))
            ->willReturn(new HttpResource(
                $this->createMock(UrlInterface::class),
                $this->createMock(MediaType::class),
                new Map('string', Attribute::class),
                $this->createMock(Readable::class)
            ));
        $publisher
            ->expects($this->never())
            ->method('__invoke');

        $this->assertNull($command(
            $this->createMock(Environment::class),
            new Arguments(
                (new Map('string', 'mixed'))
                    ->put('url', 'http://example.com')
            ),
            new Options
        ));
    }

    public function testCrawlAndPublish()
    {
        $command = new Crawl(
            $crawler = $this->createMock(Crawler::class),
            'foo',
            $publisher = $this->createMock(Publisher::class)
        );
        $crawler
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($request): bool {
                return (string) $request->url() === 'http://example.com/' &&
                    (string) $request->method() === 'GET' &&
                    (string) $request->headers()->get('user-agent') === 'User-Agent : foo';
            }))
            ->willReturn($resource = new HttpResource(
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
                $this->callback(static function($url): bool {
                    return (string) $url === 'http://example2.com/';
                })
            )
            ->willReturn(new Reference(
                $this->createMock(Identity::class),
                'foo',
                $this->createMock(UrlInterface::class)
            ));

        $this->assertNull($command(
            $this->createMock(Environment::class),
            new Arguments(
                (new Map('string', 'mixed'))
                    ->put('url', 'http://example.com')
                    ->put('publish', 'http://example2.com')
            ),
            new Options
        ));
    }
}
