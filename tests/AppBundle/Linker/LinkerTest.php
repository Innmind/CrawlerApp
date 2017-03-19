<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Linker;

use AppBundle\{
    Linker\Linker,
    LinkerInterface,
    Reference
};
use Innmind\Rest\Client\{
    ClientInterface,
    IdentityInterface,
    Link,
    ServerInterface
};
use Innmind\Url\Url;
use Innmind\Immutable\SetInterface;
use PHPUnit\Framework\TestCase;

class LinkerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            LinkerInterface::class,
            new Linker(
                $this->createMock(ClientInterface::class)
            )
        );
    }

    /**
     * @expectedException AppBundle\Exception\CantLinkResourceAcrossServersException
     */
    public function testThrowWhenLinkingResourcesOnDifferentServers()
    {
        $linker = new Linker(
            $client = $this->createMock(ClientInterface::class)
        );
        $client
            ->expects($this->never())
            ->method('server');

        $linker(
            new Reference(
                $this->createMock(IdentityInterface::class),
                'foo',
                Url::fromString('foo')
            ),
            new Reference(
                $this->createMock(IdentityInterface::class),
                'foo',
                Url::fromString('bar')
            ),
            'some rel',
            []
        );
    }

    public function testInvokation()
    {
        $linker = new Linker(
            $client = $this->createMock(ClientInterface::class)
        );
        $source = $this->createMock(IdentityInterface::class);
        $target = $this->createMock(IdentityInterface::class);
        $client
            ->expects($this->once())
            ->method('server')
            ->with('http://server.url/')
            ->willReturn(
                $server = $this->createMock(ServerInterface::class)
            );
        $server
            ->expects($this->once())
            ->method('link')
            ->with(
                'foo',
                $source,
                $this->callback(function(SetInterface $links) use ($target): bool {
                    return (string) $links->type() === Link::class &&
                        $links->size() === 1 &&
                        $links->current()->definition() === 'bar' &&
                        $links->current()->identity() === $target &&
                        $links->current()->relationship() === 'some rel' &&
                        $links->current()->parameters()->size() === 1 &&
                        $links->current()->parameters()->get('some')->key() === 'some' &&
                        $links->current()->parameters()->get('some')->value() === 'attribute';
                })
            );

        $linker(
            new Reference(
                $source,
                'foo',
                Url::fromString('http://server.url/')
            ),
            new Reference(
                $target,
                'bar',
                Url::fromString('http://server.url/')
            ),
            'some rel',
            [
                'some' => 'attribute',
            ]
        );
    }
}
