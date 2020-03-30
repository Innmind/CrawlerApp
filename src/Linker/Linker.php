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
        if ((string) $source->server() !== (string) $target->server()) {
            throw new CantLinkResourceAcrossServers($source, $target);
        }

        $map = new Map('string', Parameter::class);

        foreach ($attributes as $key => $value) {
            $map = $map->put($key, new Parameter\Parameter($key, $value));
        }

        $this
            ->client
            ->server((string) $source->server())
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
