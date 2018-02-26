<?php
declare(strict_types = 1);

namespace Tests\AppBundle;

use AppBundle\Logger;
use Monolog\{
    Logger as Monolog,
    Handler\HandlerInterface,
};
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    public function testBuild()
    {
        $logger = Logger::build('foo', $handler = $this->createMock(HandlerInterface::class));
        $handler
            ->expects($this->once())
            ->method('isHandling')
            ->with(['level' => 300])
            ->willReturn(true);
        $handler
            ->expects($this->once())
            ->method('handle')
            ->with($this->callback(static function($record): bool {
                return $record['message'] === 'watev' &&
                    $record['level_name'] === 'WARNING' &&
                    $record['channel'] === 'foo';
            }));

        $this->assertInstanceOf(Monolog::class, $logger);
        $this->assertTrue($logger->warning('watev'));
    }
}
