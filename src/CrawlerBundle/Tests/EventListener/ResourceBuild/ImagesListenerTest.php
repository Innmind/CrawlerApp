<?php

namespace CrawlerBundle\Tests\EventListener\ResourceBuild;

use CrawlerBundle\EventListener\ResourceBuild\ImagesListener;
use CrawlerBundle\Events;
use CrawlerBundle\Event\ResourcePropertyBuildEvent;
use Innmind\Rest\Client\Definition\ResourceDefinition;
use Innmind\Rest\Client\Definition\Property;
use Innmind\Rest\Client\HttpResourceInterface;
use Innmind\Crawler\HttpResource;

class ImagesListenerTest extends \PHPUnit_Framework_TestCase
{
    protected $l;

    public function setUp()
    {
        $this->l = new ImagesListener;
    }

    public function testSubscribedEvents()
    {
        $this->assertSame(
            [Events::RESOURCE_PROPERTY_BUILD => 'injectImages'],
            ImagesListener::getSubscribedEvents()
        );
    }

    /**
     * @dataProvider invalid
     */
    public function testDoesntAddImages($def, $prop, $resource)
    {
        $event = new ResourcePropertyBuildEvent($def, $prop, $resource);

        $this->assertSame(null, $this->l->injectImages($event));
        $this->assertFalse($event->hasValue());
    }

    public function testAddAlternates()
    {
        $def = $this
            ->getMockBuilder(ResourceDefinition::class)
            ->disableOriginalConstructor()
            ->getMock();
        $subResource = $this
            ->getMockBuilder(ResourceDefinition::class)
            ->disableOriginalConstructor()
            ->getMock();
        $subResource
            ->method('hasProperty')
            ->willReturn(true);
        $prop = $this
            ->getMockBuilder(Property::class)
            ->disableOriginalConstructor()
            ->setMethods(['__toString', 'getType', 'containsResource', 'getResource'])
            ->getMock();
        $prop
            ->method('__toString')
            ->willReturn('images');
        $prop
            ->method('getType')
            ->willReturn('array');
        $prop
            ->method('containsResource')
            ->willReturn(true);
        $prop
            ->method('getResource')
            ->willReturn($subResource);
        $resource = (new HttpResource('', ''))
            ->set('images', [
                ['foo', 'bar'],
                ['baz', 'foobar'],
            ]);
        $event = new ResourcePropertyBuildEvent($def, $prop, $resource);

        $this->assertSame(null, $this->l->injectImages($event));
        $this->assertTrue($event->hasValue());
        $this->assertTrue(is_array($event->getValue()));
        $this->assertSame(2, count($event->getValue()));
        $this->assertInstanceOf(HttpResourceInterface::class, $event->getValue()[0]);
        $this->assertInstanceOf(HttpResourceInterface::class, $event->getValue()[1]);
        $this->assertSame('foo', $event->getValue()[0]->get('url'));
        $this->assertSame('bar', $event->getValue()[0]->get('description'));
        $this->assertSame('baz', $event->getValue()[1]->get('url'));
        $this->assertSame('foobar', $event->getValue()[1]->get('description'));
    }

    public function invalid()
    {
        $def = $this
            ->getMockBuilder(ResourceDefinition::class)
            ->disableOriginalConstructor()
            ->getMock();
        $prop1 = $this
            ->getMockBuilder(Property::class)
            ->disableOriginalConstructor()
            ->setMethods(['__toString', 'getType', 'containsResource', 'getResource'])
            ->getMock();
        $prop2 = clone $prop1;
        $prop3 = clone $prop1;
        $prop4 = clone $prop1;
        $prop5 = clone $prop1;
        $prop6 = clone $prop1;
        $prop1
            ->method('__toString')
            ->willReturn('foo');
        $prop2
            ->method('__toString')
            ->willReturn('images');
        $prop2
            ->method('getType')
            ->willReturn('string');
        $prop3
            ->method('__toString')
            ->willReturn('images');
        $prop3
            ->method('getType')
            ->willReturn('array');
        $prop3
            ->method('containsResource')
            ->willReturn(false);
        $prop4
            ->method('__toString')
            ->willReturn('images');
        $prop4
            ->method('getType')
            ->willReturn('array');
        $prop4
            ->method('containsResource')
            ->willReturn(true);
        $subResource = $this
            ->getMockBuilder(ResourceDefinition::class)
            ->disableOriginalConstructor()
            ->getMock();
        $subResource2 = clone $subResource;
        $subResource
            ->method('hasProperty')
            ->willReturn(false);
        $subResource2
            ->method('hasProperty')
            ->willReturn(true);
        $prop5
            ->method('__toString')
            ->willReturn('images');
        $prop5
            ->method('getType')
            ->willReturn('array');
        $prop5
            ->method('containsResource')
            ->willReturn(true);
        $prop5
            ->method('getResource')
            ->willReturn($subResource);
        $prop6
            ->method('__toString')
            ->willReturn('images');
        $prop6
            ->method('getType')
            ->willReturn('array');
        $prop6
            ->method('containsResource')
            ->willReturn(true);
        $prop6
            ->method('getResource')
            ->willReturn($subResource2);

        return [
            [$def, $prop1, new HttpResource('', '')],
            [$def, $prop2, new HttpResource('', '')],
            [$def, $prop3, new HttpResource('', '')],
            [$def, $prop4, new HttpResource('', '')],
            [$def, $prop5, (new HttpResource('', ''))->set('images', [])],
            [$def, $prop6, (new HttpResource('', ''))->set('images', [])],
        ];
    }
}
