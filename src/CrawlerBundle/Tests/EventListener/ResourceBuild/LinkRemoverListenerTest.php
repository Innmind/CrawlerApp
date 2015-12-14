<?php

namespace CrawlerBundle\Tests\EventListener\ResourceBuild;

use CrawlerBundle\EventListener\ResourceBuild\LinkRemoverListener;
use CrawlerBundle\Events;
use CrawlerBundle\Event\ResourceBuildEvent;
use Innmind\Crawler\HttpResource as CrawledResource;
use Innmind\Rest\Client\Definition\ResourceDefinition;
use Innmind\Rest\Client\HttpResource;

class LinkRemoverListenerTest extends \PHPUnit_Framework_TestCase
{
    protected $l;

    public function setUp()
    {
        $this->l = new LinkRemoverListener;
    }

    public function testSubscribedEvents()
    {
        $this->assertSame(
            [Events::RESOURCE_BUILD => 'removeOwnLink'],
            LinkRemoverListener::getSubscribedEvents()
        );
    }

    public function testDoesntRemove()
    {
        $c = new CrawledResource('foo', '');
        $def = $this
            ->getMockBuilder(ResourceDefinition::class)
            ->disableOriginalConstructor()
            ->getMock();
        $r = new HttpResource;
        $e = new ResourceBuildEvent($r, $def, $c);

        $this->assertSame(null, $this->l->removeOwnLink($e));
        $this->assertFalse($r->has('links'));

        $r->set('links', []);

        $this->assertSame(null, $this->l->removeOwnLink($e));
        $this->assertSame([], $r->get('links'));

        $r->set('links', ['bar']);

        $this->assertSame(null, $this->l->removeOwnLink($e));
        $this->assertSame(['bar'], $r->get('links'));
    }

    public function testRemoveLink()
    {
        $c = new CrawledResource('foo', '');
        $def = $this
            ->getMockBuilder(ResourceDefinition::class)
            ->disableOriginalConstructor()
            ->getMock();
        $r = new HttpResource;
        $r->set('links', ['foo', 'bar']);
        $e = new ResourceBuildEvent($r, $def, $c);

        $this->assertSame(null, $this->l->removeOwnLink($e));
        $this->assertSame(['bar'], $r->get('links'));
    }
}
