<?php
declare(strict_types = 1);

namespace Tests\Crawler\RobotsTxt;

use Crawler\RobotsTxt\CacheParser;
use Innmind\RobotsTxt\{
    Parser,
    RobotsTxt,
    Parser\Walker,
};
use Innmind\Filesystem\{
    Adapter,
    File\File,
    Name,
};
use Innmind\Stream\Readable\Stream;
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
            ->method('contains')
            ->with(new Name('www.example.org.txt'))
            ->willReturn(true);
        $this
            ->filesystem
            ->expects($this->once())
            ->method('get')
            ->with(new Name('www.example.org.txt'))
            ->willReturn(
                new File(
                    new Name('foo'),
                    Stream::ofContent($expected = 'User-agent: Bar'."\n".'Allow: /foo')
                )
            );
        $this
            ->inner
            ->expects($this->never())
            ->method('__invoke');

        $robots = ($this->parser)(
            Url::of('http://user:pwd@www.example.org/robots.txt')
        );

        $this->assertInstanceOf(RobotsTxt::class, $robots);
        $this->assertSame($expected, $robots->toString());
    }

    public function testParseFromWeb()
    {
        $url = Url::of('http://user:pwd@www.example.org/robots.txt');
        $this
            ->filesystem
            ->expects($this->once())
            ->method('contains')
            ->with(new Name('www.example.org.txt'))
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
                return $file->name()->toString() === 'www.example.org.txt' &&
                    $file->content()->toString() === 'whatever';
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
            ->method('toString')
            ->willReturn('whatever');

        $robots = ($this->parser)($url);

        $this->assertSame($expected, $robots);
    }
}
