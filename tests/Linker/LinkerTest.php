<?php
declare(strict_types = 1);

namespace Tests\Crawler\Linker;

use Crawler\{
    Linker\Linker,
    Linker as LinkerInterface,
    Reference,
    Exception\CantLinkResourceAcrossServers,
};
use Innmind\Rest\Client\{
    Client,
    Identity,
    Link,
    Server,
};
use Innmind\Url\Url;
use Innmind\Immutable\Set;
use function Innmind\Immutable\first;
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
                    Url::of('foo')
                ),
                $target = new Reference(
                    $this->createMock(Identity::class),
                    'foo',
                    Url::of('bar')
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
                $this->callback(static function(Set $links) use ($target): bool {
                    return (string) $links->type() === Link::class &&
                        $links->size() === 1 &&
                        first($links)->definition() === 'bar' &&
                        first($links)->identity() === $target &&
                        first($links)->relationship() === 'some rel' &&
                        first($links)->parameters()->size() === 1 &&
                        first($links)->parameters()->get('some')->key() === 'some' &&
                        first($links)->parameters()->get('some')->value() === 'attribute';
                })
            );

        $linker(
            new Reference(
                $source,
                'foo',
                Url::of('http://server.url/')
            ),
            new Reference(
                $target,
                'bar',
                Url::of('http://server.url/')
            ),
            'some rel',
            [
                'some' => 'attribute',
            ]
        );
    }
}
