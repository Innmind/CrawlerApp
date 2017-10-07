<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Linker;

use AppBundle\{
    Linker\Linker,
    Linker as LinkerInterface,
    Reference,
    Exception\CantLinkResourceAcrossServers
};
use Innmind\Rest\Client\{
    Client,
    Identity,
    Link,
    Server
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
                $this->createMock(Client::class)
            )
        );
    }

    public function testThrowWhenLinkingResourcesOnDifferentServers()
    {
        $linker = new Linker(
            $client = $this->createMock(Client::class)
        );
        $client
            ->expects($this->never())
            ->method('server');

        try {
            $linker(
                $source = new Reference(
                    $this->createMock(Identity::class),
                    'foo',
                    Url::fromString('foo')
                ),
                $target = new Reference(
                    $this->createMock(Identity::class),
                    'foo',
                    Url::fromString('bar')
                ),
                'some rel',
                []
            );
            $this->fail('it should throw');
        } catch (CantLinkResourceAcrossServers $e) {
            $this->assertSame($source, $e->source());
            $this->assertSame($target, $e->target());
        }
    }

    public function testInvokation()
    {
        $linker = new Linker(
            $client = $this->createMock(Client::class)
        );
        $source = $this->createMock(Identity::class);
        $target = $this->createMock(Identity::class);
        $client
            ->expects($this->once())
            ->method('server')
            ->with('http://server.url/')
            ->willReturn(
                $server = $this->createMock(Server::class)
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
