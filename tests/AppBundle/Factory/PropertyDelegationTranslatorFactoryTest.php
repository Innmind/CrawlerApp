<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Factory;

use AppBundle\{
    Factory\PropertyDelegationTranslatorFactory,
    Translator\PropertyTranslator,
    Translator\Property\DelegationTranslator
};
use PHPUnit\Framework\TestCase;

class PropertyDelegationTranslatorFactoryTest extends TestCase
{
    public function testMake()
    {
        $translator = PropertyDelegationTranslatorFactory::make([
            'foo' => $this->createMock(PropertyTranslator::class)
        ]);

        $this->assertInstanceOf(DelegationTranslator::class, $translator);
    }
}
