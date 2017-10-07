<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Crawler;

use AppBundle\{
    Crawler\TracerAwareCrawler,
    CrawlTracer,
    Exception\UrlCannotBeCrawledException
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
        $crawler = new TracerAwareCrawler(
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
            ->method('execute');
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->exactly(2))
            ->method('url')
            ->willReturn($url);

        try {
            $crawler->execute($request);
            $this->fail('it should throw');
        } catch (UrlCannotBeCrawledException $e) {
            $this->assertSame($url, $e->url());
        }
    }

    public function testCrawl()
    {
        $crawler = new TracerAwareCrawler(
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
            ->method('execute')
            ->with($request)
            ->willReturn(
                $expected = new HttpResource(
                    $url,
                    $this->createMock(MediaType::class),
                    new Map('string', Attribute::class),
                    $this->createMock(Readable::class)
                )
            );

        $this->assertSame($expected, $crawler->execute($request));
    }
}
