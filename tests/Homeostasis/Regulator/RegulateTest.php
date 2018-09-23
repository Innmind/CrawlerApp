<?php
declare(strict_types = 1);

namespace Tests\Crawler\Homeostasis\Regulator;

use Crawler\Homeostasis\Regulator\Regulate;
use Innmind\Homeostasis\{
    Regulator,
    Strategy,
    Actuator,
    Exception\HomeostasisAlreadyInProcess
};
use PHPUnit\Framework\TestCase;

class RegulateTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Regulator::class,
            new Regulate(
                $this->createMock(Regulator::class),
                $this->createMock(Actuator::class)
            )
        );
    }

    public function testDoesntThrowWhenHomeostasisAlreadyInProcess()
    {
        $regulate = new Regulate(
            $regulator = $this->createMock(Regulator::class),
            $actuator = $this->createMock(Actuator::class)
        );
        $regulator
            ->expects($this->once())
            ->method('__invoke')
            ->will($this->throwException(new HomeostasisAlreadyInProcess));
        $actuator
            ->expects($this->once())
            ->method('holdSteady');

        $this->assertSame(Strategy::holdSteady(), $regulate());
    }

    public function testRegulate()
    {
        $regulate = new Regulate(
            $regulator = $this->createMock(Regulator::class),
            $actuator = $this->createMock(Actuator::class)
        );
        $regulator
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn(Strategy::increase());
        $actuator
            ->expects($this->never())
            ->method('holdSteady');

        $this->assertSame(Strategy::increase(), $regulate());
    }
}
