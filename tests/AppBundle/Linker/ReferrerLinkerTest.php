<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Linker;

use AppBundle\{
    Linker\ReferrerLinker,
    LinkerInterface,
    Reference
};
use Innmind\Rest\Client\IdentityInterface;
use Innmind\Url\UrlInterface;
use PHPUnit\Framework\TestCase;

class ReferrerLinkerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            LinkerInterface::class,
            new ReferrerLinker(
                $this->createMock(LinkerInterface::class)
            )
        );
    }

    public function testDoesntAlterWhenNotReferrer()
    {
        $linker = new ReferrerLinker(
            $inner = $this->createMock(LinkerInterface::class)
        );
        $source = new Reference(
            $this->createMock(IdentityInterface::class),
            'foo',
            $this->createMock(UrlInterface::class)
        );
        $target = new Reference(
            $this->createMock(IdentityInterface::class),
            'foo',
            $this->createMock(UrlInterface::class)
        );
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $source,
                $target,
                'some rel',
                ['foo' => 'bar']
            );

        $this->assertNull($linker($source, $target, 'some rel', ['foo' => 'bar']));
    }

    public function testAlterWhenReferrer()
    {
        $linker = new ReferrerLinker(
            $inner = $this->createMock(LinkerInterface::class)
        );
        $source = new Reference(
            $this->createMock(IdentityInterface::class),
            'foo',
            $this->createMock(UrlInterface::class)
        );
        $target = new Reference(
            $this->createMock(IdentityInterface::class),
            'foo',
            $this->createMock(UrlInterface::class)
        );
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with(
                $this->callback(function(Reference $altered) use ($source): bool {
                    return $altered->identity() === $source->identity() &&
                        $altered->definition() === 'web.resource' &&
                        $altered->server() === $source->server();
                }),
                $this->callback(function(Reference $altered) use ($target): bool {
                    return $altered->identity() === $target->identity() &&
                        $altered->definition() === 'web.resource' &&
                        $altered->server() === $target->server();
                }),
                'referrer',
                ['foo' => 'bar']
            );

        $this->assertNull($linker($source, $target, 'referrer', ['foo' => 'bar']));
    }
}
