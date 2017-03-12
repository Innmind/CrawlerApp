<?php
declare(strict_types = 1);

namespace Tests\AppBundle\CrawlTracer;

use AppBundle\{
    CrawlTracer\CrawlTracer,
    CrawlTracerInterface
};
use Innmind\Filesystem\{
    AdapterInterface,
    Directory,
    File,
    Stream\NullStream,
    Stream\StringStream
};
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    PointInTimeInterface,
    Format\ISO8601
};
use Innmind\Url\{
    Url,
    Authority\Host
};
use PHPUnit\Framework\TestCase;

class CrawlTracerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            CrawlTracerInterface::class,
            new CrawlTracer(
                $this->createMock(AdapterInterface::class),
                $this->createMock(TimeContinuumInterface::class)
            )
        );
    }

    public function testTrace()
    {
        $filesystem = $this->createMock(AdapterInterface::class);
        $filesystem
            ->expects($this->at(0))
            ->method('has')
            ->with('hits')
            ->willReturn(false);
        $filesystem
            ->expects($this->at(1))
            ->method('add')
            ->with($this->callback(function(Directory $dir): bool {
                return (string) $dir->name() === 'hits';
            }));
        $filesystem
            ->expects($this->at(2))
            ->method('has')
            ->with('urls.txt')
            ->willReturn(false);
        $filesystem
            ->expects($this->at(3))
            ->method('add')
            ->with($this->callback(function(File $file): bool {
                return (string) $file->name() === 'urls.txt' &&
                    (string) $file->content() === '';
            }));
        $filesystem
            ->expects($this->at(4))
            ->method('get')
            ->with('hits')
            ->willReturn(new Directory('hits'));
        $filesystem
            ->expects($this->at(5))
            ->method('add')
            ->with($this->callback(function(Directory $dir): bool {
                return $dir->has('www.example.com.txt') &&
                    (string) $dir->get('www.example.com.txt')->content() === 'some date';
            }));
        $filesystem
            ->expects($this->at(6))
            ->method('get')
            ->with('urls.txt')
            ->willReturn(new File('urls.txt', new NullStream));
        $filesystem
            ->expects($this->at(7))
            ->method('get')
            ->with('urls.txt')
            ->willReturn(new File('urls.txt', new NullStream));
        $filesystem
            ->expects($this->at(8))
            ->method('add')
            ->with($this->callback(function(File $file): bool {
                return (string) $file->name() === 'urls.txt' &&
                    (string) $file->content() === 'http://www.example.com/foo?some#fragment'."\n";
            }));
        $tracer = new CrawlTracer(
            $filesystem,
            $clock = $this->createMock(TimeContinuumInterface::class)
        );
        $clock
            ->expects($this->once())
            ->method('now')
            ->willReturn(
                $now = $this->createMock(PointInTimeInterface::class)
            );
        $now
            ->expects($this->once())
            ->method('format')
            ->with(new ISO8601)
            ->willReturn('some date');

        $this->assertSame(
            $tracer,
            $tracer->trace(Url::fromString('http://www.example.com/foo?some#fragment'))
        );
    }

    public function testTraceDoesntAddUrlTwice()
    {
        $filesystem = $this->createMock(AdapterInterface::class);
        $filesystem
            ->expects($this->at(0))
            ->method('has')
            ->with('hits')
            ->willReturn(false);
        $filesystem
            ->expects($this->at(1))
            ->method('add')
            ->with($this->callback(function(Directory $dir): bool {
                return (string) $dir->name() === 'hits';
            }));
        $filesystem
            ->expects($this->at(2))
            ->method('has')
            ->with('urls.txt')
            ->willReturn(false);
        $filesystem
            ->expects($this->at(3))
            ->method('add')
            ->with($this->callback(function(File $file): bool {
                return (string) $file->name() === 'urls.txt' &&
                    (string) $file->content() === '';
            }));
        $filesystem
            ->expects($this->at(4))
            ->method('get')
            ->with('hits')
            ->willReturn(new Directory('hits'));
        $filesystem
            ->expects($this->at(5))
            ->method('add')
            ->with($this->callback(function(Directory $dir): bool {
                return $dir->has('www.example.com.txt') &&
                    (string) $dir->get('www.example.com.txt')->content() === 'some date';
            }));
        $filesystem
            ->expects($this->at(6))
            ->method('get')
            ->with('urls.txt')
            ->willReturn(
                new File(
                    'urls.txt',
                    new StringStream('http://www.example.com/foo?some#fragment'."\n")
                )
            );
        $filesystem
            ->expects($this->exactly(3))
            ->method('add');
        $tracer = new CrawlTracer(
            $filesystem,
            $clock = $this->createMock(TimeContinuumInterface::class)
        );
        $clock
            ->expects($this->once())
            ->method('now')
            ->willReturn(
                $now = $this->createMock(PointInTimeInterface::class)
            );
        $now
            ->expects($this->once())
            ->method('format')
            ->with(new ISO8601)
            ->willReturn('some date');

        $this->assertSame(
            $tracer,
            $tracer->trace(Url::fromString('http://www.example.com/foo?some#fragment'))
        );
    }

    public function testIsKnown()
    {
        $tracer = new CrawlTracer(
            $filesystem = $this->createMock(AdapterInterface::class),
            $this->createMock(TimeContinuumInterface::class)
        );
        $filesystem
            ->expects($this->at(0))
            ->method('get')
            ->with('urls.txt')
            ->willReturn(new File('urls.txt', new NullStream));
        $filesystem
            ->expects($this->at(1))
            ->method('get')
            ->with('urls.txt')
            ->willReturn(
                new File(
                    'urls.txt',
                    new StringStream('/foo')
                )
            );

        $this->assertFalse($tracer->isKnown(Url::fromString('/foo')));
        $this->assertTrue($tracer->isKnown(Url::fromString('/foo')));
    }

    public function testLastHit()
    {
        $tracer = new CrawlTracer(
            $filesystem = $this->createMock(AdapterInterface::class),
            $clock = $this->createMock(TimeContinuumInterface::class)
        );
        $filesystem
            ->expects($this->once())
            ->method('get')
            ->with('hits')
            ->willReturn(
                (new Directory('hits'))
                    ->add(
                        new File(
                            'example.com.txt',
                            new StringStream('some date')
                        )
                    )
            );
        $clock
            ->expects($this->once())
            ->method('at')
            ->with('some date')
            ->willReturn($expected = $this->createMock(PointInTimeInterface::class));

        $this->assertSame($expected, $tracer->lastHit(new Host('example.com')));
    }

    /**
     * @expectedException AppBundle\Exception\HostNeverHitException
     */
    public function testThrowWhenHostNeverHit()
    {
        $tracer = new CrawlTracer(
            $filesystem = $this->createMock(AdapterInterface::class),
            $clock = $this->createMock(TimeContinuumInterface::class)
        );
        $filesystem
            ->expects($this->once())
            ->method('get')
            ->with('hits')
            ->willReturn(
                new Directory('hits')
            );

        $tracer->lastHit(new Host('example.com'));
    }
}