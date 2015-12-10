<?php

namespace CrawlerBundle\Tests;

use CrawlerBundle\Publisher;
use CrawlerBundle\ResourceFactory;
use Innmind\Rest\Client\HttpResourceInterface;
use Innmind\Rest\Client\Definition\ResourceDefinition;
use Innmind\Crawler\HttpResource as CrawlResource;
use Innmind\RestBundle\Client;
use Innmind\RestBundle\Client\Server;
use Innmind\Crawler\CrawlerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Stopwatch\Stopwatch;
use Psr\Log\NullLogger;

class PublisherTest extends \PHPUnit_Framework_TestCase
{
    protected $p;

    public function setUp()
    {
        $this->p = new Publisher(
            $client = $this
                ->getMockBuilder(Client::class)
                ->disableOriginalConstructor()
                ->getMock(),
            $crawler = $this->getMock(CrawlerInterface::class),
            new NullLogger,
            new ResourceFactory(
                new EventDispatcher
            )
        );
        $crawler
            ->method('crawl')
            ->will($this->returnCallback(function($request) {
                if ($request->getUrl() === 'foo') {
                    return new CrawlResource('', 'image/png');
                } else {
                    return new CrawlResource('foo', 'text/html');
                }
            }));
        $crawler
            ->method('getStopwatch')
            ->willReturn(new Stopwatch);
        $crawler
            ->method('release')
            ->willReturn($crawler);
        $server = $this
            ->getMockBuilder(Server::class)
            ->disableOriginalConstructor()
            ->getMock();
        $server
            ->method('getResources')
            ->willReturn([
                'foo' => $def = $this
                    ->getMockBuilder(ResourceDefinition::class)
                    ->disableOriginalConstructor()
                    ->setMethods(['hasMeta', 'getMeta', 'getProperties'])
                    ->getMock()
            ]);
        $def
            ->method('hasMeta')
            ->willReturn(true);
        $def
            ->method('getMeta')
            ->willReturn('image/*');
        $def
            ->method('getProperties')
            ->willReturn([]);
        $client
            ->method('getServer')
            ->willReturn($server);
    }

    public function testPublisher()
    {
        $resource = $this->p->publish('foo', 'bar');

        $this->assertInstanceOf(HttpResourceInterface::class, $resource);
    }

    /**
     * @expectedException CrawlerBundle\Exception\EndpointNotFoundException
     * @expectedExceptionMessage No endpoint was found for the content type "text/html" at "bar" for "foo"
     */
    public function testThrowWhenNoEndpointFound()
    {
        $this->p->publish('bar', 'bar');
    }
}
