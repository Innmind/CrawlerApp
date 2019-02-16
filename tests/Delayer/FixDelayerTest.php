<?php
declare(strict_types = 1);

namespace Tests\Crawler\Delayer;

use Crawler\{
    Delayer\FixDelayer,
    Delayer,
};
use Innmind\TimeWarp\Halt;
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    PeriodInterface,
};
use Innmind\Url\UrlInterface;
use PHPUnit\Framework\TestCase;

class FixDelayerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Delayer::class,
            new FixDelayer(
                $this->createMock(Halt::class),
                $this->createMock(TimeContinuumInterface::class)
            )
        );
    }

    public function testInvokation()
    {
        $delay = new FixDelayer(
            $halt = $this->createMock(Halt::class),
            $clock = $this->createMock(TimeContinuumInterface::class),
            $period = $this->createMock(PeriodInterface::class)
        );
        $halt
            ->expects($this->once())
            ->method('__invoke')
            ->with($clock, $period);

        $this->assertNull($delay($this->createMock(UrlInterface::class)));
    }
}
