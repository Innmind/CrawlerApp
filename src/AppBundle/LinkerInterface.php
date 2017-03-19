<?php
declare(strict_types = 1);

namespace AppBundle;

use AppBundle\Reference;

interface LinkerInterface
{
    public function __invoke(
        Reference $source,
        Reference $target,
        string $relationship,
        array $attributes
    ): void;
}
