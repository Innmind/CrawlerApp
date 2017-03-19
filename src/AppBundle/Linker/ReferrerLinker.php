<?php
declare(strict_types = 1);

namespace AppBundle\Linker;

use AppBundle\{
    LinkerInterface,
    Reference
};

final class ReferrerLinker implements LinkerInterface
{
    private $linker;

    public function __construct(LinkerInterface $linker)
    {
        $this->linker = $linker;
    }

    public function __invoke(
        Reference $source,
        Reference $target,
        string $relationship,
        array $attributes
    ): void {
        if ($relationship === 'referrer') {
            $source = new Reference(
                $source->identity(),
                'web.resource',
                $source->server()
            );
            $target = new Reference(
                $target->identity(),
                'web.resource',
                $target->server()
            );
            $attributes = [];
        }

        ($this->linker)($source, $target, $relationship, $attributes);
    }
}
