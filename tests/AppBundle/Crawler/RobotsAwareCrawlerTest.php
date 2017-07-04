<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Crawler;

use AppBundle\{
    Crawler\RobotsAwareCrawler,
    Exception\UrlCannotBeCrawledException
};
use Innmind\RobotsTxt\{
    ParserInterface,
    RobotsTxtInterface,
    Exception\FileNotFoundException
};
use Innmind\Crawler\{
    CrawlerInterface,
    HttpResource,
    HttpResource\AttributeInterface
};
use Innmind\Url\{
    UrlInterface,
    Url
};
use Innmind\Http\Message\RequestInterface;
use Innmind\Filesystem\{
    MediaTypeInterface,
    StreamInterface
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class RobotsAwareCrawlerTest extends TestCase
{
    private $crawler;
    private $parser;
    private $inner;

    public function setUp()
    {
        $this->crawler = new RobotsAwareCrawler(
            $this->parser = $this->createMock(ParserInterface::class),
            $this->inner = $this->createMock(CrawlerInterface::class),
            'user agent'
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            CrawlerInterface::class,
            $this->crawler
        );
    }

    public function testCrawlWhenNoRobots()
    {
        $this
            ->parser
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function(UrlInterface $url): bool {
                return (string) $url === 'http://example.com/robots.txt';
            }))
            ->will(
                $this->throwException(new FileNotFoundException)
            );
        $request = $this->createMock(RequestInterface::class);
        $request
            ->expects($this->once())
            ->method('url')
            ->willReturn($url = Url::fromString('http://example.com/foo?bar#baz'));
        $this
            ->inner
            ->expects($this->once())
            ->method('execute')
            ->with($request)
            ->willReturn(
                $expected = new HttpResource(
                    $url,
                    $this->createMock(MediaTypeInterface::class),
                    new Map('string', AttributeInterface::class),
                    $this->createMock(StreamInterface::class)
                )
            );

        $resource = $this->crawler->execute($request);

        $this->assertSame($expected, $resource);
    }

    public function testCrawlWhenRobotsAllowsUrl()
    {
        $request = $this->createMock(RequestInterface::class);
        $request
            ->expects($this->exactly(2))
            ->method('url')
            ->willReturn($url = Url::fromString('http://example.com/foo?bar#baz'));
        $this
            ->parser
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function(UrlInterface $url): bool {
                return (string) $url === 'http://example.com/robots.txt';
            }))
            ->willReturn(
                $robots = $this->createMock(RobotsTxtInterface::class)
            );
        $robots
            ->expects($this->once())
            ->method('disallows')
            ->with('user agent', $url)
            ->willReturn(false);
        $this
            ->inner
            ->expects($this->once())
            ->method('execute')
            ->with($request)
            ->willReturn(
                $expected = new HttpResource(
                    $url,
                    $this->createMock(MediaTypeInterface::class),
                    new Map('string', AttributeInterface::class),
                    $this->createMock(StreamInterface::class)
                )
            );

        $resource = $this->crawler->execute($request);

        $this->assertSame($expected, $resource);
    }

    public function testThrowWhenRobotsDisallows()
    {
        $request = $this->createMock(RequestInterface::class);
        $request
            ->expects($this->exactly(3))
            ->method('url')
            ->willReturn($url = Url::fromString('http://example.com/foo?bar#baz'));
        $this
            ->parser
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function(UrlInterface $url): bool {
                return (string) $url === 'http://example.com/robots.txt';
            }))
            ->willReturn(
                $robots = $this->createMock(RobotsTxtInterface::class)
            );
        $robots
            ->expects($this->once())
            ->method('disallows')
            ->with('user agent', $url)
            ->willReturn(true);
        $this
            ->inner
            ->expects($this->never())
            ->method('execute');

        try {
            $this->crawler->execute($request);
            $this->fail('it should throw');
        } catch (UrlCannotBeCrawledException $e) {
            $this->assertSame($url, $e->url());
        }
    }
}
