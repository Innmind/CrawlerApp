<?php
declare(strict_types = 1);

namespace Crawler;

use Innmind\Url\Url;

final class SameUrlAs
{
    private string $url;

    public function __construct(Url $url)
    {
        $this->url = $url->withoutFragment()->toString();
    }

    public function __invoke(Url $url): bool
    {
        return $this->url === $url->withoutFragment()->toString();
    }
}
