<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Crawler;

use AppBundle\{
    Crawler\DelayerAwareCrawler,
    DelayerInterface
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

class DelayerAwareCrawlerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            CrawlerInterface::class,
            new DelayerAwareCrawler(
                $this->createMock(DelayerInterface::class),
                $this->createMock(CrawlerInterface::class)
            )
        );
    }

    public function testExecute()
    {
        $crawler = new DelayerAwareCrawler(
            $delayer = $this->createMock(DelayerInterface::class),
            $inner = $this->createMock(CrawlerInterface::class)
        );
        $url = $this->createMock(UrlInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $request
            ->expects($this->once())
            ->method('url')
            ->willReturn($url);
        $delayer
            ->expects($this->once())
            ->method('__invoke')
            ->with($url);
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
