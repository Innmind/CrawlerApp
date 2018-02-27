<?php
declare(strict_types = 1);

namespace Crawler;

use Innmind\Url\{
    UrlInterface,
    NullFragment
};

final class SameUrlAs
{
    private $url;

    public function __construct(UrlInterface $url)
    {
        $this->url = $url->withFragment(new NullFragment);
    }

    public function __invoke(UrlInterface $url): bool
    {
        return (string) $this->url === (string) $url->withFragment(new NullFragment);
    }
}
