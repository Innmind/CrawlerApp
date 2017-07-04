<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Crawler;

use AppBundle\{
    Crawler\TracerAwareCrawler,
    CrawlTracerInterface,
    Exception\UrlCannotBeCrawledException
};
use Innmind\Crawler\{
    CrawlerInterface,
    HttpResource,
    HttpResource\AttributeInterface
};
use Innmind\Url\UrlInterface;
use Innmind\Http\Message\RequestInterface;
use Innmind\Filesystem\{
    MediaTypeInterface,
    StreamInterface
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class TracerAwareCrawlerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            CrawlerInterface::class,
            new TracerAwareCrawler(
                $this->createMock(CrawlTracerInterface::class),
                $this->createMock(CrawlerInterface::class)
            )
        );
    }

    public function testThrowWhenResourceAlreadyCrawled()
    {
        $crawler = new TracerAwareCrawler(
            $tracer = $this->createMock(CrawlTracerInterface::class),
            $inner = $this->createMock(CrawlerInterface::class)
        );
        $url = $this->createMock(UrlInterface::class);
        $tracer
            ->expects($this->once())
            ->method('isKnown')
            ->with($url)
            ->willReturn(true);
        $inner
            ->expects($this->never())
            ->method('execute');
        $request = $this->createMock(RequestInterface::class);
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
            $tracer = $this->createMock(CrawlTracerInterface::class),
            $inner = $this->createMock(CrawlerInterface::class)
        );
        $url = $this->createMock(UrlInterface::class);
        $tracer
            ->expects($this->once())
            ->method('isKnown')
            ->with($url)
            ->willReturn(false);
        $request = $this->createMock(RequestInterface::class);
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
                    $this->createMock(MediaTypeInterface::class),
                    new Map('string', AttributeInterface::class),
                    $this->createMock(StreamInterface::class)
                )
            );

        $this->assertSame($expected, $crawler->execute($request));
    }
}
