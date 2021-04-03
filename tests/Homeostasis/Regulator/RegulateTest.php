<?php
declare(strict_types = 1);

namespace Tests\Crawler\Homeostasis\Regulator;

use Crawler\Homeostasis\Regulator\Regulate;
use Innmind\IPC\{
    IPC,
    Process,
    Process\Name,
};
use PHPUnit\Framework\TestCase;

class RegulateTest extends TestCase
{
    public function testPingTheHomeostasisDaemonWhenAvailable()
    {
        $regulate = new Regulate(
            $ipc = $this->createMock(IPC::class),
            $name = new Name('foo')
        );
        $ipc
            ->expects($this->at(0))
            ->method('wait')
            ->with($name);
        $ipc
            ->expects($this->at(1))
            ->method('exist')
            ->with($name)
            ->willReturn(true);
        $ipc
            ->expects($this->at(2))
            ->method('get')
            ->with($name)
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(static function() {
                return true; //assert that at least one message is sent
            }));
        $process
            ->expects($this->once())
            ->method('close');

        $this->assertNull($regulate());
    }

    public function testDoesntPingTheHomeostasisDaemonWhenNotAvailable()
    {
        $regulate = new Regulate(
            $ipc = $this->createMock(IPC::class),
            $name = new Name('foo')
        );
        $ipc
            ->expects($this->at(0))
            ->method('wait')
            ->with($name);
        $ipc
            ->expects($this->at(1))
            ->method('exist')
            ->with($name)
            ->willReturn(false);
        $ipc
            ->expects($this->never())
            ->method('get');

        $this->assertNull($regulate());
    }
}
