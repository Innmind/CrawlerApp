<?php
declare(strict_types = 1);

namespace AppBundle\Linker;

use AppBundle\{
    LinkerInterface,
    Reference,
    Exception\CantLinkResourceAcrossServersException
};
use Innmind\Rest\Client\{
    ClientInterface,
    Link,
    Link\ParameterInterface,
    Link\Parameter
};
use Innmind\Immutable\{
    Map,
    Set
};

final class Linker implements LinkerInterface
{
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function __invoke(
        Reference $source,
        Reference $target,
        string $relationship,
        array $attributes
    ): void {
        if ((string) $source->server() !== (string) $target->server()) {
            throw new CantLinkResourceAcrossServersException($source, $target);
        }

        $map = new Map('string', ParameterInterface::class);

        foreach ($attributes as $key => $value) {
            $map = $map->put($key, new Parameter($key, $value));
        }

        $this
            ->client
            ->server((string) $source->server())
            ->link(
                $source->definition(),
                $source->identity(),
                (new Set(Link::class))->add(
                    new Link(
                        $target->definition(),
                        $target->identity(),
                        $relationship,
                        $map
                    )
                )
            );
    }
}
