<?php
declare(strict_types = 1);

namespace Tests\Crawler\RobotsTxt;

use Crawler\RobotsTxt\CacheParser;
use Innmind\RobotsTxt\{
    Parser,
    RobotsTxt,
    Parser\Walker
};
use Innmind\Filesystem\{
    Adapter,
    File\File,
    Stream\StringStream
};
use Innmind\Url\Url;
use PHPUnit\Framework\TestCase;

class CacheParserTest extends TestCase
{
    private $parser;
    private $inner;
    private $filesystem;

    public function setUp(): void
    {
        $this->parser = new CacheParser(
            $this->inner = $this->createMock(Parser::class),
            new Walker,
            $this->filesystem = $this->createMock(Adapter::class)
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            Parser::class,
            $this->parser
        );
    }

    public function testParseFromCache()
    {
        $this
            ->filesystem
            ->expects($this->once())
            ->method('has')
            ->with('www.example.org.txt')
            ->willReturn(true);
        $this
            ->filesystem
            ->expects($this->once())
            ->method('get')
            ->with('www.example.org.txt')
            ->willReturn(
                new File(
                    'foo',
                    new StringStream($expected = 'User-agent: Bar'."\n".'Allow: /foo')
                )
            );
        $this
            ->inner
            ->expects($this->never())
            ->method('__invoke');

        $robots = ($this->parser)(
            Url::fromString('http://user:pwd@www.example.org/robots.txt')
        );

        $this->assertInstanceOf(RobotsTxt::class, $robots);
        $this->assertSame($expected, (string) $robots);
    }

    public function testParseFromWeb()
    {
        $url = Url::fromString('http://user:pwd@www.example.org/robots.txt');
        $this
            ->filesystem
            ->expects($this->once())
            ->method('has')
            ->with('www.example.org.txt')
            ->willReturn(false);
        $this
            ->filesystem
            ->expects($this->never())
            ->method('get');
        $this
            ->filesystem
            ->expects($this->once())
            ->method('add')
            ->with($this->callback(function(File $file): bool {
                return (string) $file->name() === 'www.example.org.txt' &&
                    (string) $file->content() === 'whatever';
            }));
        $this
            ->inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($url)
            ->willReturn(
                $expected = $this->createMock(RobotsTxt::class)
            );
        $expected
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('whatever');

        $robots = ($this->parser)($url);

        $this->assertSame($expected, $robots);
    }
}
