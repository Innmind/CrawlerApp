<?php
declare(strict_types = 1);

namespace Tests\Crawler\Crawler;

use Crawler\{
    Crawler\RobotsAwareCrawler,
    Exception\UrlCannotBeCrawled,
};
use Innmind\RobotsTxt\{
    Parser,
    RobotsTxt,
    Exception\FileNotFound,
};
use Innmind\Crawler\{
    Crawler,
    HttpResource,
    HttpResource\Attribute,
};
use Innmind\Url\Url;
use Innmind\Http\Message\Request;
use Innmind\MediaType\MediaType;
use Innmind\Stream\Readable;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class RobotsAwareCrawlerTest extends TestCase
{
    private $crawl;
    private $parser;
    private $inner;

    public function setUp(): void
    {
        $this->crawl = new RobotsAwareCrawler(
            $this->parser = $this->createMock(Parser::class),
            $this->inner = $this->createMock(Crawler::class),
            'user agent'
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            Crawler::class,
            $this->crawl
        );
    }

    public function testCrawlWhenNoRobots()
    {
        $this
            ->parser
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function(Url $url): bool {
                return $url->toString() === 'http://example.com/robots.txt';
            }))
            ->will(
                $this->throwException(new FileNotFound)
            );
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('url')
            ->willReturn($url = Url::of('http://example.com/foo?bar#baz'));
        $this
            ->inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn(
                $expected = new HttpResource(
                    $url,
                    MediaType::null(),
                    Map::of('string', Attribute::class),
                    $this->createMock(Readable::class)
                )
            );

        $resource = ($this->crawl)($request);

        $this->assertSame($expected, $resource);
    }

    public function testCrawlWhenRobotsAllowsUrl()
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->exactly(2))
            ->method('url')
            ->willReturn($url = Url::of('http://example.com/foo?bar#baz'));
        $this
            ->parser
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function(Url $url): bool {
                return $url->toString() === 'http://example.com/robots.txt';
            }))
            ->willReturn(
                $robots = $this->createMock(RobotsTxt::class)
            );
        $robots
            ->expects($this->once())
            ->method('disallows')
            ->with('user agent', $url)
            ->willReturn(false);
        $this
            ->inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn(
                $expected = new HttpResource(
                    $url,
                    MediaType::null(),
                    Map::of('string', Attribute::class),
                    $this->createMock(Readable::class)
                )
            );

        $resource = ($this->crawl)($request);

        $this->assertSame($expected, $resource);
    }

    public function testThrowWhenRobotsDisallows()
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->exactly(3))
            ->method('url')
            ->willReturn($url = Url::of('http://example.com/foo?bar#baz'));
        $this
            ->parser
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function(Url $url): bool {
                return $url->toString() === 'http://example.com/robots.txt';
            }))
            ->willReturn(
                $robots = $this->createMock(RobotsTxt::class)
            );
        $robots
            ->expects($this->once())
            ->method('disallows')
            ->with('user agent', $url)
            ->willReturn(true);
        $this
            ->inner
            ->expects($this->never())
            ->method('__invoke');

        try {
            ($this->crawl)($request);
            $this->fail('it should throw');
        } catch (UrlCannotBeCrawled $e) {
            $this->assertSame($url, $e->url());
        }
    }
}
