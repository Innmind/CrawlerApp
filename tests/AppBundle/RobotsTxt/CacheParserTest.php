<?php
declare(strict_types = 1);

namespace Tests\AppBundle\RobotsTxt;

use AppBundle\RobotsTxt\CacheParser;
use Innmind\RobotsTxt\{
    ParserInterface,
    RobotsTxtInterface,
    Parser\Walker
};
use Innmind\Filesystem\{
    AdapterInterface,
    File,
    Stream\StringStream
};
use Innmind\Url\Url;
use PHPUnit\Framework\TestCase;

class CacheParserTest extends TestCase
{
    private $parser;
    private $inner;
    private $filesystem;

    public function setUp()
    {
        $this->parser = new CacheParser(
            $this->inner = $this->createMock(ParserInterface::class),
            new Walker,
            $this->filesystem = $this->createMock(AdapterInterface::class)
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            ParserInterface::class,
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

        $this->assertInstanceOf(RobotsTxtInterface::class, $robots);
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
                $expected = $this->createMock(RobotsTxtInterface::class)
            );
        $expected
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('whatever');

        $robots = ($this->parser)($url);

        $this->assertSame($expected, $robots);
    }
}
