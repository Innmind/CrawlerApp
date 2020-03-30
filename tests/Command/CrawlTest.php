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
use Innmind\Url\Url;
use Innmind\MediaType\MediaType;
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

        $this->assertSame($expected, $command->toString());
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
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return $request->url()->toString() === 'http://example.com/' &&
                    $request->method()->toString() === 'GET' &&
                    $request->protocolVersion()->toString() === '2.0' &&
                    $request->headers()->get('user-agent')->toString() === 'User-Agent: foo';
            }))
            ->willReturn(new HttpResource(
                Url::of('example.com'),
                MediaType::null(),
                Map::of('string', Attribute::class),
                $this->createMock(Readable::class)
            ));
        $publisher
            ->expects($this->never())
            ->method('__invoke');

        $this->assertNull($command(
            $this->createMock(Environment::class),
            new Arguments(
                Map::of('string', 'string')
                    ('url', 'http://example.com')
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
            ->method('__invoke')
            ->with($this->callback(static function($request): bool {
                return $request->url()->toString() === 'http://example.com/' &&
                    $request->method()->toString() === 'GET' &&
                    $request->headers()->get('user-agent')->toString() === 'User-Agent: foo';
            }))
            ->willReturn($resource = new HttpResource(
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
                $this->callback(static function($url): bool {
                    return $url->toString() === 'http://example2.com/';
                })
            )
            ->willReturn(new Reference(
                $this->createMock(Identity::class),
                'foo',
                Url::of('example.com')
            ));

        $this->assertNull($command(
            $this->createMock(Environment::class),
            new Arguments(
                Map::of('string', 'string')
                    ('url', 'http://example.com')
                    ('publish', 'http://example2.com')
            ),
            new Options
        ));
    }
}
