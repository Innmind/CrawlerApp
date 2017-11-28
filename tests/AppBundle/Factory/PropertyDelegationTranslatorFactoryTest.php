<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Factory;

use AppBundle\{
    Factory\PropertyDelegationTranslatorFactory,
    Translator\PropertyTranslator,
    Translator\Property\DelegationTranslator
};
use Innmind\Crawler\{
    HttpResource,
    HttpResource\Attribute
};
use Innmind\Url\UrlInterface;
use Innmind\Filesystem\MediaType\NullMediaType;
use Innmind\Stream\Readable;
use Innmind\Rest\Client\Definition\{
    Property,
    Type,
    Access
};
use Innmind\Immutable\{
    Map,
    Set
};
use PHPUnit\Framework\TestCase;

class PropertyDelegationTranslatorFactoryTest extends TestCase
{
    public function testMake()
    {
        $translator = PropertyDelegationTranslatorFactory::make([
            'foo' => $mock = $this->createMock(PropertyTranslator::class)
        ]);
        $mock
            ->expects($this->once())
            ->method('supports')
            ->willReturn(true);

        $this->assertInstanceOf(DelegationTranslator::class, $translator);
        $this->assertTrue(
            $translator->supports(
                new HttpResource(
                    $this->createMock(UrlInterface::class),
                    new NullMediaType,
                    new Map('string', Attribute::class),
                    $this->createMock(Readable::class)
                ),
                new Property(
                    'foo',
                    $this->createMock(Type::class),
                    new Access,
                    new Set('string'),
                    true
                )
            )
        );
    }
}
