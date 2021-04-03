<?php
declare(strict_types = 1);

namespace Tests\Crawler;

use function Crawler\bootstrap;
use Innmind\CLI\{
    Environment,
    Framework\Application,
};
use Innmind\OperatingSystem\{
    OperatingSystem,
    CurrentProcess,
};
use Innmind\Url\Path;
use Innmind\Server\Status;
use Innmind\Server\Control;
use Innmind\Immutable\{
    Sequence,
    Map,
};
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    public function testBootstrap()
    {
        $os = $this->createMock(OperatingSystem::class);
        $os
            ->method('status')
            ->willReturn($status = $this->createMock(Status\Server::class));
        $status
            ->method('tmp')
            ->willReturn(Path::of(\getcwd().'/var/'));
        $os
            ->method('process')
            ->willReturn($process = $this->createMock(CurrentProcess::class));
        $process
            ->method('id')
            ->willReturn(new Control\Server\Process\Pid(42));
        $env = $this->createMock(Environment::class);
        $env
            ->method('arguments')
            ->willReturn(Sequence::strings('crawler'));
        $env
            ->method('variables')
            ->willReturn(
                Map::of('string', 'string')
                    ('AMQP_SERVER', '')
                    ('API_KEY', '')
            );
        $env
            ->method('workingDirectory')
            ->willReturn(Path::of(__DIR__.'/../'));

        $app = bootstrap(Application::of($env, $os));

        $this->assertNull($app->run());
    }
}
