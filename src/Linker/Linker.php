<?php
declare(strict_types = 1);

namespace Crawler\Linker;

use Crawler\{
    Linker as LinkerInterface,
    Reference,
    Exception\CantLinkResourceAcrossServers,
};
use Innmind\Rest\Client\{
    Client,
    Link,
    Link\Parameter,
};
use Innmind\Immutable\{
    Map,
    Set,
};

final class Linker implements LinkerInterface
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function __invoke(
        Reference $source,
        Reference $target,
        string $relationship,
        array $attributes
    ): void {
        if ($source->server()->toString() !== $target->server()->toString()) {
            throw new CantLinkResourceAcrossServers($source, $target);
        }

        $map = Map::of('string', Parameter::class);

        foreach ($attributes as $key => $value) {
            $map = $map->put($key, new Parameter\Parameter($key, $value));
        }

        $this
            ->client
            ->server($source->server()->toString())
            ->link(
                $source->definition(),
                $source->identity(),
                Set::of(
                    Link::class,
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
