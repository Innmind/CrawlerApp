<?php
declare(strict_types = 1);

namespace Tests\Crawler\Crawler;

use Crawler\{
    Crawler\DelayerAwareCrawler,
    Delayer,
};
use Innmind\Crawler\{
    Crawler,
    HttpResource,
    HttpResource\Attribute,
};
use Innmind\Url\UrlInterface;
use Innmind\Http\Message\Request;
use Innmind\Filesystem\MediaType;
use Innmind\Stream\Readable;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class DelayerAwareCrawlerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Crawler::class,
            new DelayerAwareCrawler(
                $this->createMock(Delayer::class),
                $this->createMock(Crawler::class)
            )
        );
    }

    public function testExecute()
    {
        $crawl = new DelayerAwareCrawler(
            $delayer = $this->createMock(Delayer::class),
            $inner = $this->createMock(Crawler::class)
        );
        $url = $this->createMock(UrlInterface::class);
        $request = $this->createMock(Request::class);
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
