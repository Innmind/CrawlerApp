<?php

namespace CrawlerBundle\Tests\Event;

use CrawlerBundle\Event\ResourceBuildEvent;
use Innmind\Rest\Client\HttpResource;
use Innmind\Rest\Client\Definition\ResourceDefinition;
use Innmind\Crawler\HttpResource as CrawledResource;

class ResourceBuildEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $event = new ResourceBuildEvent(
            $res = $this
                ->getMockBuilder(HttpResource::class)
                ->disableOriginalConstructor()
                ->getMock(),
            $def = $this
                ->getMockBuilder(ResourceDefinition::class)
                ->disableOriginalConstructor()
                ->getMock(),
            $crawled = $this
                ->getMockBuilder(CrawledResource::class)
                ->disableOriginalConstructor()
                ->getMock()
        );

        $this->assertSame($res, $event->getRestResource());
        $this->assertSame($def, $event->getDefinition());
        $this->assertSame($crawled, $event->getCrawledResource());
    }
}
