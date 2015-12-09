<?php

namespace CrawlerBundle\Tests;

use CrawlerBundle\ResourceFactory;
use CrawlerBundle\Events;
use CrawlerBundle\Event\ResourcePropertyBuildEvent;
use CrawlerBundle\Event\ResourceBuildEvent;
use Innmind\Rest\Client\Definition\Property;
use Innmind\Rest\Client\Definition\ResourceDefinition;
use Innmind\Rest\Client\HttpResource;
use Innmind\Crawler\HttpResource as CrawledResource;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ResourceFactoryTest extends \PHPUnit_Framework_TestCase
{
    protected $f;
    protected $d;

    public function setUp()
    {
        $this->f = new ResourceFactory(
            $this->d = new EventDispatcher
        );
    }

    public function testMake()
    {
        $propertyFired = $resourceFired = false;
        $this->d->addListener(
            Events::RESOURCE_PROPERTY_BUILD,
            function ($event) use (&$propertyFired) {
                $propertyFired = true;
                $this->assertInstanceOf(ResourcePropertyBuildEvent::class, $event);
            }
        );
        $this->d->addListener(
            Events::RESOURCE_BUILD,
            function ($event) use (&$resourceFired) {
                $resourceFired = true;
                $this->assertInstanceOf(ResourceBuildEvent::class, $event);
            }
        );

        $p1 = $this
            ->getMockBuilder(Property::class)
            ->disableOriginalConstructor()
            ->setMethods(['__toString', 'containsResource', 'getType'])
            ->getMock();
        $p1
            ->method('__toString')
            ->willReturn('p1');
        $p1
            ->method('containsResource')
            ->willReturn(true);
        $p1
            ->method('getType')
            ->willReturn('resource');
        $p2 = $this
            ->getMockBuilder(Property::class)
            ->disableOriginalConstructor()
            ->setMethods(['__toString', 'containsResource', 'getType'])
            ->getMock();
        $p2
            ->method('__toString')
            ->willReturn('p2');
        $p2
            ->method('containsResource')
            ->willReturn(false);
        $p2
            ->method('getType')
            ->willReturn('bool');
        $p3 = $this
            ->getMockBuilder(Property::class)
            ->disableOriginalConstructor()
            ->setMethods(['__toString', 'containsResource', 'getType'])
            ->getMock();
        $p3
            ->method('__toString')
            ->willReturn('p3');
        $p3
            ->method('containsResource')
            ->willReturn(false);
        $p3
            ->method('getType')
            ->willReturn('string');
        $properties = [$p1, $p2, $p3];
        $def = $this
            ->getMockBuilder(ResourceDefinition::class)
            ->disableOriginalConstructor()
            ->getMock();
        $def
            ->method('getProperties')
            ->willReturn($properties);
        $crawled = new CrawledResource('', '');
        $crawled
            ->set('p1', ['foo', 'bar'])
            ->set('p2', 'foo')
            ->set('p3', 'baz');

        $resource = $this->f->make($def, $crawled);

        $this->assertInstanceOf(HttpResource::class, $resource);
        $this->assertTrue($resource->has('p3'));
        $this->assertSame('baz', $resource->get('p3'));
        $this->assertFalse($resource->has('p1'));
        $this->assertFalse($resource->has('p2'));
    }
}
