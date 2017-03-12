<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Delayer;

use AppBundle\{
    Delayer\ThresholdDelayer,
    DelayerInterface
};
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    PointInTimeInterface,
    ElapsedPeriod
};
use Innmind\Url\UrlInterface;
use PHPUnit\Framework\TestCase;

class ThresholdDelayerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            DelayerInterface::class,
            new ThresholdDelayer(
                $this->createMock(DelayerInterface::class),
                $this->createMock(DelayerInterface::class),
                $this->createMock(TimeContinuumInterface::class),
                0
            )
        );
    }

    public function testDoesntCallFallback()
    {
        $delayer = new ThresholdDelayer(
            $attempt = $this->createMock(DelayerInterface::class),
            $fallback = $this->createMock(DelayerInterface::class),
            $clock = $this->createMock(TimeContinuumInterface::class),
            1000
        );
        $url = $this->createMock(UrlInterface::class);
        $attempt
            ->expects($this->once())
            ->method('__invoke')
            ->with($url);
        $fallback
            ->expects($this->never())
            ->method('__invoke');
        $clock
            ->expects($this->exactly(2))
            ->method('now')
            ->will($this->onConsecutiveCalls(
                $start = $this->createMock(PointInTimeInterface::class),
                $end = $this->createMock(PointInTimeInterface::class)
            ));
        $end
            ->expects($this->once())
            ->method('elapsedSince')
            ->with($start)
            ->willReturn(new ElapsedPeriod(2000));

        $this->assertNull($delayer($url));
    }

    public function testCallFallback()
    {
        $delayer = new ThresholdDelayer(
            $attempt = $this->createMock(DelayerInterface::class),
            $fallback = $this->createMock(DelayerInterface::class),
            $clock = $this->createMock(TimeContinuumInterface::class),
            1000
        );
        $url = $this->createMock(UrlInterface::class);
        $attempt
            ->expects($this->once())
            ->method('__invoke')
            ->with($url);
        $fallback
            ->expects($this->once())
            ->method('__invoke')
            ->with($url);
        $clock
            ->expects($this->exactly(2))
            ->method('now')
            ->will($this->onConsecutiveCalls(
                $start = $this->createMock(PointInTimeInterface::class),
                $end = $this->createMock(PointInTimeInterface::class)
            ));
        $end
            ->expects($this->once())
            ->method('elapsedSince')
            ->with($start)
            ->willReturn(new ElapsedPeriod(500));

        $this->assertNull($delayer($url));
    }

    /**
     * @expectedException AppBundle\Exception\InvalidArgumentException
     */
    public function testThrowWhenNegativeThreshold()
    {
        new ThresholdDelayer(
            $this->createMock(DelayerInterface::class),
            $this->createMock(DelayerInterface::class),
            $this->createMock(TimeContinuumInterface::class),
            -1
        );
    }
}
