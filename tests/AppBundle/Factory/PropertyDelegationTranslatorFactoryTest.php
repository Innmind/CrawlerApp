<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Factory;

use AppBundle\{
    Factory\PropertyDelegationTranslatorFactory,
    Translator\PropertyTranslatorInterface,
    Translator\Property\DelegationTranslator
};
use PHPUnit\Framework\TestCase;

class PropertyDelegationTranslatorFactoryTest extends TestCase
{
    public function testMake()
    {
        $translator = PropertyDelegationTranslatorFactory::make([
            'foo' => $this->createMock(PropertyTranslatorInterface::class)
        ]);

        $this->assertInstanceOf(DelegationTranslator::class, $translator);
    }
}
