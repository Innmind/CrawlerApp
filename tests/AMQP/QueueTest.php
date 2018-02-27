<?php
declare(strict_types = 1);

namespace Tests\Crawler\AMQP;

use Crawler\AMQP\Queue;
use Innmind\AMQP\Model\Queue\Declaration;
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    public function testNamed()
    {
        $queue = Queue::named('foo');

        $this->assertInstanceOf(Declaration::class, $queue);
        $this->assertSame('foo', $queue->name());
        $this->assertTrue($queue->isDurable());
    }
}
