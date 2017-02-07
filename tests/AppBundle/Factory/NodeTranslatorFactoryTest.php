<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Factory;

use AppBundle\Factory\NodeTranslatorFactory;
use Innmind\Xml\Translator\NodeTranslator;

class NodeTranslatorFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testMake()
    {
        $translator = NodeTranslatorFactory::make();

        $this->assertInstanceOf(NodeTranslator::class, $translator);
    }
}
