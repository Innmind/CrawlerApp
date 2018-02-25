<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Homeostasis;

use AppBundle\Homeostasis\Factors;
use Innmind\Homeostasis\Factor\{
    Cpu,
    Log,
};
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\Server\Status\Server;
use Innmind\Filesystem\Adapter;
use Innmind\LogReader\Reader;
use PHPUnit\Framework\TestCase;

class FactorsTest extends TestCase
{
    public function testCpu()
    {
        $this->assertInstanceOf(
            Cpu::class,
            Factors::cpu(
                $this->createMock(TimeContinuumInterface::class),
                $this->createMock(Server::class)
            )
        );
    }

    public function testLog()
    {
        $this->assertInstanceOf(
            Log::class,
            Factors::log(
                $this->createMock(TimeContinuumInterface::class),
                $this->createMock(Reader::class),
                $this->createMock(Adapter::class)
            )
        );
    }
}
