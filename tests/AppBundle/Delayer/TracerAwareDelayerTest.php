<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Delayer;

use AppBundle\{
    Delayer\TracerAwareDelayer,
    DelayerInterface,
    CrawlTracerInterface,
    Exception\HostNeverHitException
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
            DelayerInterface::class,
            new TracerAwareDelayer(
                $this->createMock(CrawlTracerInterface::class),
                $this->createMock(DelayerInterface::class),
                $this->createMock(TimeContinuumInterface::class),
                5
            )
        );
    }

    public function testDelay()
    {
        $delayer = new TracerAwareDelayer(
            $tracer = $this->createMock(CrawlTracerInterface::class),
            $inner = $this->createMock(DelayerInterface::class),
            $clock = $this->createMock(TimeContinuumInterface::class),
            5
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
            $tracer = $this->createMock(CrawlTracerInterface::class),
            $inner = $this->createMock(DelayerInterface::class),
            $clock = $this->createMock(TimeContinuumInterface::class),
            5
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
            $tracer = $this->createMock(CrawlTracerInterface::class),
            $inner = $this->createMock(DelayerInterface::class),
            $clock = $this->createMock(TimeContinuumInterface::class),
            5
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
                $this->throwException(new HostNeverHitException)
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
