<?php

namespace CrawlerBundle\Tests\Event;

use CrawlerBundle\Event\ResourcePropertyBuildEvent;
use Innmind\Rest\Client\Definition\ResourceDefinition;
use Innmind\Rest\Client\Definition\Property;
use Innmind\Crawler\HttpResource;

class ResourcePropertyBuildEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $event = new ResourcePropertyBuildEvent(
            $def = $this
                ->getMockBuilder(ResourceDefinition::class)
                ->disableOriginalConstructor()
                ->getMock(),
            $prop = $this
                ->getMockBuilder(Property::class)
                ->disableOriginalConstructor()
                ->getMock(),
            $res = $this
                ->getMockBuilder(HttpResource::class)
                ->disableOriginalConstructor()
                ->getMock()
        );

        $this->assertSame($def, $event->getDefinition());
        $this->assertSame($prop, $event->getProperty());
        $this->assertSame($res, $event->getResource());
        $this->assertFalse($event->hasValue());
        $this->assertSame(null, $event->setValue(false));
        $this->assertTrue($event->hasValue());
        $this->assertSame(false, $event->getValue());
        $this->assertTrue($event->isPropagationStopped());
    }
}
