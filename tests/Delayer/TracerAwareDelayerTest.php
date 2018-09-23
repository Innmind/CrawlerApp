<?php
declare(strict_types = 1);

namespace Tests\Crawler\Delayer;

use Crawler\{
    Delayer\TracerAwareDelayer,
    Delayer,
    CrawlTracer,
    Exception\HostNeverHit
};
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    PointInTimeInterface,
    ElapsedPeriod
};
use Innmind\Url\{
    UrlInterface,
    AuthorityInterface,
    Authority\HostInterface
};
use PHPUnit\Framework\TestCase;

class TracerAwareDelayerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Delayer::class,
            new TracerAwareDelayer(
                $this->createMock(CrawlTracer::class),
                $this->createMock(Delayer::class),
                $this->createMock(TimeContinuumInterface::class)
            )
        );
    }

    public function testDelay()
    {
        $delayer = new TracerAwareDelayer(
            $tracer = $this->createMock(CrawlTracer::class),
            $inner = $this->createMock(Delayer::class),
            $clock = $this->createMock(TimeContinuumInterface::class),
            new ElapsedPeriod(5000)
        );
        $url = $this->createMock(UrlInterface::class);
        $url
            ->expects($this->once())
            ->method('authority')
            ->willReturn(
                $authority = $this->createMock(AuthorityInterface::class)
            );
        $authority
            ->expects($this->once())
            ->method('host')
            ->willReturn(
                $host = $this->createMock(HostInterface::class)
            );
        $tracer
            ->expects($this->once())
            ->method('lastHit')
            ->with($host)
            ->willReturn(
                $lastHit = $this->createMock(PointInTimeInterface::class)
            );
        $clock
            ->expects($this->once())
            ->method('now')
            ->willReturn(
                $now = $this->createMock(PointInTimeInterface::class)
            );
        $now
            ->expects($this->once())
            ->method('elapsedSince')
            ->with($lastHit)
            ->willReturn(new ElapsedPeriod(1000));
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($url);

        $this->assertNull($delayer($url));
    }

    public function testDoesntDelayWhenHitAfterThreshold()
    {
        $delayer = new TracerAwareDelayer(
            $tracer = $this->createMock(CrawlTracer::class),
            $inner = $this->createMock(Delayer::class),
            $clock = $this->createMock(TimeContinuumInterface::class),
            new ElapsedPeriod(5000)
        );
        $url = $this->createMock(UrlInterface::class);
        $url
            ->expects($this->once())
            ->method('authority')
            ->willReturn(
                $authority = $this->createMock(AuthorityInterface::class)
            );
        $authority
            ->expects($this->once())
            ->method('host')
            ->willReturn(
                $host = $this->createMock(HostInterface::class)
            );
        $tracer
            ->expects($this->once())
            ->method('lastHit')
            ->with($host)
            ->willReturn(
                $lastHit = $this->createMock(PointInTimeInterface::class)
            );
        $clock
            ->expects($this->once())
            ->method('now')
            ->willReturn(
                $now = $this->createMock(PointInTimeInterface::class)
            );
        $now
            ->expects($this->once())
            ->method('elapsedSince')
            ->with($lastHit)
            ->willReturn(new ElapsedPeriod(6000));
        $inner
            ->expects($this->never())
            ->method('__invoke');

        $this->assertNull($delayer($url));
    }

    public function testDoesntDelayWhenNeverHit()
    {
        $delayer = new TracerAwareDelayer(
            $tracer = $this->createMock(CrawlTracer::class),
            $inner = $this->createMock(Delayer::class),
            $clock = $this->createMock(TimeContinuumInterface::class),
            new ElapsedPeriod(5000)
        );
        $url = $this->createMock(UrlInterface::class);
        $url
            ->expects($this->once())
            ->method('authority')
            ->willReturn(
                $authority = $this->createMock(AuthorityInterface::class)
            );
        $authority
            ->expects($this->once())
            ->method('host')
            ->willReturn(
                $host = $this->createMock(HostInterface::class)
            );
        $tracer
            ->expects($this->once())
            ->method('lastHit')
            ->with($host)
            ->will(
                $this->throwException(new HostNeverHit)
            );
        $clock
            ->expects($this->never())
            ->method('now');
        $inner
            ->expects($this->never())
            ->method('__invoke');

        $this->assertNull($delayer($url));
    }
}
