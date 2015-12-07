<?php

namespace CrawlerBundle\Tests\DependencyInjection\Compiler;

use CrawlerBundle\DependencyInjection\Compiler\RegisterParserPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class RegisterParserPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $p = new RegisterParserPass;
        $c = new ContainerBuilder;
        $c->setDefinition('parser', $def = new Definition('stdClass'));
        $c->setDefinition('p1', $p1 = new Definition('stdClass'));
        $c->setDefinition('p2', $p2 = new Definition('stdClass'));
        $c->setDefinition('p3', $p3 = new Definition('stdClass'));
        $p1->addTag('parser', ['priority' => 100]);
        $p2->addTag('parser');
        $p3->addTag('parser', ['priority' => 10]);

        $this->assertSame(null, $p->process($c));
        $calls = $def->getMethodCalls();
        $this->assertSame(3, count($calls));
        $this->assertSame('addPass', $calls[0][0]);
        $this->assertSame('addPass', $calls[1][0]);
        $this->assertSame('addPass', $calls[2][0]);
        $this->assertInstanceOf(Reference::class, $calls[0][1][0]);
        $this->assertInstanceOf(Reference::class, $calls[1][1][0]);
        $this->assertInstanceOf(Reference::class, $calls[2][1][0]);
        $this->assertSame('p1', (string) $calls[0][1][0]);
        $this->assertSame('p3', (string) $calls[1][1][0]);
        $this->assertSame('p2', (string) $calls[2][1][0]);
    }
}
