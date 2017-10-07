<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Delayer;

use AppBundle\{
    Delayer\FixDelayer,
    Delayer
};
use Innmind\Url\UrlInterface;
use PHPUnit\Framework\TestCase;

class FixDelayerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Delayer::class,
            new FixDelayer(1)
        );
    }

    public function testInvokation()
    {
        $start = microtime(true);
        $this->assertNull((new FixDelayer(500))($this->createMock(UrlInterface::class)));
        $this->assertTrue(microtime(true) - $start >= 0.5);
        $this->assertTrue(microtime(true) - $start < 1);
    }

    /**
     * @expectedException AppBundle\Exception\DomainException
     */
    public function testThrowWhenNegativeSleepTime()
    {
        new FixDelayer(-1);
    }
}
