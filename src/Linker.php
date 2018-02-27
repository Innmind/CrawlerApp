<?php
declare(strict_types = 1);

namespace Crawler;

interface Linker
{
    public function __invoke(
        Reference $source,
        Reference $target,
        string $relationship,
        array $attributes
    ): void;
}
