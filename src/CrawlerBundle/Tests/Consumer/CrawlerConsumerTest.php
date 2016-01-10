<?php

namespace CrawlerBundle\Tests\Consumer;

use CrawlerBundle\Consumer\CrawlerConsumer;
use CrawlerBundle\Publisher;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\NullLogger;

class CrawlerConsumerTest extends \PHPUnit_Framework_TestCase
{
    protected $c;

    public function setUp()
    {
        $this->c = new CrawlerConsumer(
            $p = $this
                ->getMockBuilder(Publisher::class)
                ->disableOriginalConstructor()
                ->getMock(),
            new NullLogger
        );
        $p
            ->method('publish')
            ->will($this->returnCallback(function($toCrawl) {
                if ($toCrawl === 'fail') {
                    throw new \Exception;
                }
            }));
    }

    public function testPublish()
    {
        $message = new AMQPMessage;
        $message->body = serialize([
            'url' => 'good',
            'server' => 'http://foo',
            'uuid' => '42',
        ]);

        $this->assertTrue($this->c->execute($message));
    }

    public function testError()
    {
        $message = new AMQPMessage;
        $message->body = serialize([
            'url' => 'fail',
            'server' => 'http://foo',
            'uuid' => '42',
        ]);

        $this->assertFalse($this->c->execute($message));
    }
}
