<?php
declare(strict_types = 1);

namespace Tests\Crawler\Crawler;

use Crawler\Crawler\XmlReaderAwareCrawler;
use Innmind\Crawler\{
    Crawler,
    HttpResource,
    HttpResource\Attribute,
};
use Innmind\Xml\{
    Node,
    Reader\Cache\Storage,
};
use Innmind\Filesystem\MediaType;
use Innmind\Stream\Readable;
use Innmind\Http\Message\Request;
use Innmind\Url\UrlInterface;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class XmlReaderAwareCrawlerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Crawler::class,
            new XmlReaderAwareCrawler(
                new Storage,
                $this->createMock(Crawler::class)
            )
        );
    }

    public function testCrawl()
    {
        $crawl = new XmlReaderAwareCrawler(
            $cache = new Storage,
            $inner = $this->createMock(Crawler::class)
        );
        $stream = $this->createMock(Readable::class);
        $cache->add($stream, $this->createMock(Node::class));
        $request = $this->createMock(Request::class);
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn(
                $expected = new HttpResource(
                    $this->createMock(UrlInterface::class),
                    $this->createMock(MediaType::class),
                    new Map('string', Attribute::class),
                    $stream
                )
            );

        $resource = $crawl($request);

        $this->assertSame($expected, $resource);
        $this->assertFalse($cache->contains($stream));
    }
}
