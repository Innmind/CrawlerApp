<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Factory;

use AppBundle\Factory\NodeTranslatorFactory;
use Innmind\Xml\Translator\NodeTranslator;
use PHPUnit\Framework\TestCase;

class NodeTranslatorFactoryTest extends TestCase
{
    public function testMake()
    {
        $translator = NodeTranslatorFactory::make();

        $this->assertInstanceOf(NodeTranslator::class, $translator);
    }
}
