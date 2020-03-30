<?php
declare(strict_types = 1);

namespace Tests\Crawler\CrawlTracer;

use Crawler\{
    CrawlTracer\CrawlTracer,
    CrawlTracer as CrawlTracerInterface,
    Exception\HostNeverHit,
};
use Innmind\Filesystem\{
    Adapter,
    Directory\Directory,
    File\File,
    Stream\NullStream,
    Name,
};
use Innmind\Stream\Readable\Stream;
use Innmind\TimeContinuum\{
    Clock,
    PointInTime,
    Earth\Format\ISO8601,
};
use Innmind\Url\{
    Url,
    Authority\Host,
};
use PHPUnit\Framework\TestCase;

class CrawlTracerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            CrawlTracerInterface::class,
            new CrawlTracer(
                $this->createMock(Adapter::class),
                $this->createMock(Clock::class)
            )
        );
    }

    public function testTrace()
    {
        $filesystem = $this->createMock(Adapter::class);
        $filesystem
            ->expects($this->at(0))
            ->method('contains')
            ->with(new Name('hits'))
            ->willReturn(false);
        $filesystem
            ->expects($this->at(1))
            ->method('add')
            ->with($this->callback(function(Directory $dir): bool {
                return $dir->name()->toString() === 'hits';
            }));
        $filesystem
            ->expects($this->at(2))
            ->method('contains')
            ->with(new Name('urls.txt'))
            ->willReturn(false);
        $filesystem
            ->expects($this->at(3))
            ->method('add')
            ->with($this->callback(function(File $file): bool {
                return $file->name()->toString() === 'urls.txt' &&
                    $file->content()->toString() === '';
            }));
        $filesystem
            ->expects($this->at(4))
            ->method('get')
            ->with(new Name('hits'))
            ->willReturn(Directory::named('hits'));
        $filesystem
            ->expects($this->at(5))
            ->method('add')
            ->with($this->callback(function(Directory $dir): bool {
                return $dir->contains(new Name('www.example.com.txt')) &&
                    $dir->get(new Name('www.example.com.txt'))->content()->toString() === 'some date';
            }));
        $filesystem
            ->expects($this->at(6))
            ->method('get')
            ->with(new Name('urls.txt'))
            ->willReturn(File::named('urls.txt', new NullStream));
        $filesystem
            ->expects($this->at(7))
            ->method('get')
            ->with(new Name('urls.txt'))
            ->willReturn(File::named('urls.txt', new NullStream));
        $filesystem
            ->expects($this->at(8))
            ->method('add')
            ->with($this->callback(function(File $file): bool {
                return $file->name()->toString() === 'urls.txt' &&
                    $file->content()->toString() === 'http://www.example.com/foo?some'."\n";
            }));
        $tracer = new CrawlTracer(
            $filesystem,
            $clock = $this->createMock(Clock::class)
        );
        $clock
            ->expects($this->once())
            ->method('now')
            ->willReturn(
                $now = $this->createMock(PointInTime::class)
            );
        $now
            ->expects($this->once())
            ->method('format')
            ->with(new ISO8601)
            ->willReturn('some date');

        $this->assertSame(
            $tracer,
            $tracer->trace(Url::of('http://www.example.com/foo?some#fragment'))
        );
    }

    public function testTraceDoesntAddUrlTwice()
    {
        $filesystem = $this->createMock(Adapter::class);
        $filesystem
            ->expects($this->at(0))
            ->method('contains')
            ->with(new Name('hits'))
            ->willReturn(false);
        $filesystem
            ->expects($this->at(1))
            ->method('add')
            ->with($this->callback(function(Directory $dir): bool {
                return $dir->name()->toString() === 'hits';
            }));
        $filesystem
            ->expects($this->at(2))
            ->method('contains')
            ->with(new Name('urls.txt'))
            ->willReturn(false);
        $filesystem
            ->expects($this->at(3))
            ->method('add')
            ->with($this->callback(function(File $file): bool {
                return $file->name()->toString() === 'urls.txt' &&
                    $file->content()->toString() === '';
            }));
        $filesystem
            ->expects($this->at(4))
            ->method('get')
            ->with(new Name('hits'))
            ->willReturn(Directory::named('hits'));
        $filesystem
            ->expects($this->at(5))
            ->method('add')
            ->with($this->callback(function(Directory $dir): bool {
                return $dir->contains(new Name('www.example.com.txt')) &&
                    $dir->get(new Name('www.example.com.txt'))->content()->toString() === 'some date';
            }));
        $filesystem
            ->expects($this->at(6))
            ->method('get')
            ->with(new Name('urls.txt'))
            ->willReturn(
                File::named(
                    'urls.txt',
                    Stream::ofContent('http://www.example.com/foo?some#other-fragment'."\n")
                )
            );
        $filesystem
            ->expects($this->exactly(3))
            ->method('add');
        $tracer = new CrawlTracer(
            $filesystem,
            $clock = $this->createMock(Clock::class)
        );
        $clock
            ->expects($this->once())
            ->method('now')
            ->willReturn(
                $now = $this->createMock(PointInTime::class)
            );
        $now
            ->expects($this->once())
            ->method('format')
            ->with(new ISO8601)
            ->willReturn('some date');

        $this->assertSame(
            $tracer,
            $tracer->trace(Url::of('http://www.example.com/foo?some#fragment'))
        );
    }

    public function testKnows()
    {
        $tracer = new CrawlTracer(
            $filesystem = $this->createMock(Adapter::class),
            $this->createMock(Clock::class)
        );
        $filesystem
            ->expects($this->at(0))
            ->method('get')
            ->with(new Name('urls.txt'))
            ->willReturn(File::named('urls.txt', new NullStream));
        $filesystem
            ->expects($this->at(1))
            ->method('get')
            ->with(new Name('urls.txt'))
            ->willReturn(
                File::named(
                    'urls.txt',
                    Stream::ofContent('/foo')
                )
            );
        $filesystem
            ->expects($this->at(2))
            ->method('get')
            ->with(new Name('urls.txt'))
            ->willReturn(
                File::named(
                    'urls.txt',
                    Stream::ofContent('/foo')
                )
            );

        $this->assertFalse($tracer->knows(Url::of('/foo')));
        $this->assertTrue($tracer->knows(Url::of('/foo')));
        $this->assertTrue($tracer->knows(Url::of('/foo#bar')));
    }

    public function testLastHit()
    {
        $tracer = new CrawlTracer(
            $filesystem = $this->createMock(Adapter::class),
            $clock = $this->createMock(Clock::class)
        );
        $filesystem
            ->expects($this->once())
            ->method('get')
            ->with(new Name('hits'))
            ->willReturn(
                Directory::named('hits')
                    ->add(
                        File::named(
                            'example.com.txt',
                            Stream::ofContent('some date')
                        )
                    )
            );
        $clock
            ->expects($this->once())
            ->method('at')
            ->with('some date')
            ->willReturn($expected = $this->createMock(PointInTime::class));

        $this->assertSame($expected, $tracer->lastHit(Host::of('example.com')));
    }

    public function testThrowWhenHostNeverHit()
    {
        $tracer = new CrawlTracer(
            $filesystem = $this->createMock(Adapter::class),
            $clock = $this->createMock(Clock::class)
        );
        $filesystem
            ->expects($this->once())
            ->method('get')
            ->with(new Name('hits'))
            ->willReturn(
                Directory::named('hits')
            );

        $this->expectException(HostNeverHit::class);

        $tracer->lastHit(Host::of('example.com'));
    }
}
