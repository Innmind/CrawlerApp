<?php
declare(strict_types = 1);

namespace Tests\Crawler\AMQP;

use Crawler\AMQP\Producer;
use Innmind\AMQP\{
    Client,
    Producers,
};
use PHPUnit\Framework\TestCase;

class ProducerTest extends TestCase
{
    public function testGet()
    {
        $producers = new Producers(
            $this->createMock(Client::class),
            'foo'
        );

        $producer = Producer::get($producers, 'foo');

        $this->assertSame($producers->get('foo'), $producer);
    }
}
