<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Crawler;

use AppBundle\Crawler\XmlReaderAwareCrawler;
use Innmind\Crawler\{
    Crawler,
    HttpResource,
    HttpResource\Attribute
};
use Innmind\Xml\{
    ReaderInterface,
    NodeInterface,
    Reader\CacheReader
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
                new CacheReader($this->createMock(ReaderInterface::class)),
                $this->createMock(Crawler::class)
            )
        );
    }

    public function testCrawl()
    {
        $crawler = new XmlReaderAwareCrawler(
            $cache = new CacheReader(
                $reader = $this->createMock(ReaderInterface::class)
            ),
            $inner = $this->createMock(Crawler::class)
        );
        $stream = $this->createMock(Readable::class);
        $reader
            ->expects($this->exactly(2))
            ->method('read')
            ->with($stream)
            ->willReturn($this->createMock(NodeInterface::class));
        $cache->read($stream);
        $cache->read($stream);
        $request = $this->createMock(Request::class);
        $inner
            ->expects($this->once())
            ->method('execute')
            ->with($request)
            ->willReturn(
                $expected = new HttpResource(
                    $this->createMock(UrlInterface::class),
                    $this->createMock(MediaType::class),
                    new Map('string', Attribute::class),
                    $stream
                )
            );

        $resource = $crawler->execute($request);

        $this->assertSame($expected, $resource);
        $cache->read($stream); //if the stream is indeed removed it will call the inner reader
    }
}
