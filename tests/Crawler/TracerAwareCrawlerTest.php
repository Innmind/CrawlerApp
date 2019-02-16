<?php
declare(strict_types = 1);

namespace Tests\Crawler\Crawler;

use Crawler\{
    Crawler\TracerAwareCrawler,
    CrawlTracer,
    Exception\UrlCannotBeCrawled
};
use Innmind\Crawler\{
    Crawler,
    HttpResource,
    HttpResource\Attribute
};
use Innmind\Url\UrlInterface;
use Innmind\Http\Message\Request;
use Innmind\Filesystem\MediaType;
use Innmind\Stream\Readable;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class TracerAwareCrawlerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Crawler::class,
            new TracerAwareCrawler(
                $this->createMock(CrawlTracer::class),
                $this->createMock(Crawler::class)
            )
        );
    }

    public function testThrowWhenResourceAlreadyCrawled()
    {
        $crawl = new TracerAwareCrawler(
            $tracer = $this->createMock(CrawlTracer::class),
            $inner = $this->createMock(Crawler::class)
        );
        $url = $this->createMock(UrlInterface::class);
        $tracer
            ->expects($this->once())
            ->method('knows')
            ->with($url)
            ->willReturn(true);
        $inner
            ->expects($this->never())
            ->method('__invoke');
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->exactly(2))
            ->method('url')
            ->willReturn($url);

        try {
            $crawl($request);
            $this->fail('it should throw');
        } catch (UrlCannotBeCrawled $e) {
            $this->assertSame($url, $e->url());
        }
    }

    public function testCrawl()
    {
        $crawl = new TracerAwareCrawler(
            $tracer = $this->createMock(CrawlTracer::class),
            $inner = $this->createMock(Crawler::class)
        );
        $url = $this->createMock(UrlInterface::class);
        $tracer
            ->expects($this->once())
            ->method('knows')
            ->with($url)
            ->willReturn(false);
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->exactly(2))
            ->method('url')
            ->willReturn($url);
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn(
                $expected = new HttpResource(
                    $url,
                    $this->createMock(MediaType::class),
                    new Map('string', Attribute::class),
                    $this->createMock(Readable::class)
                )
            );

        $this->assertSame($expected, $crawl($request));
    }
}
