<?php
declare(strict_types = 1);

namespace Tests\Crawler\Delayer;

use Crawler\{
    Delayer\FixDelayer,
    Delayer,
};
use Innmind\OperatingSystem\CurrentProcess;
use Innmind\TimeContinuum\Period;
use Innmind\Url\Url;
use PHPUnit\Framework\TestCase;

class FixDelayerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Delayer::class,
            new FixDelayer(
                $this->createMock(CurrentProcess::class)
            )
        );
    }

    public function testInvokation()
    {
        $delay = new FixDelayer(
            $process = $this->createMock(CurrentProcess::class),
            $period = $this->createMock(Period::class)
        );
        $process
            ->expects($this->once())
            ->method('halt')
            ->with($period);

        $this->assertNull($delay(Url::of('example.com')));
    }
}
