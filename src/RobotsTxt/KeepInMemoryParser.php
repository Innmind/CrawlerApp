<?php
declare(strict_types = 1);

namespace Crawler\RobotsTxt;

use Innmind\RobotsTxt\{
    Parser,
    RobotsTxt,
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\Map;

final class KeepInMemoryParser implements Parser
{
    private $parser;
    private $cache;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
        $this->cache = new Map('string', RobotsTxt::class);
    }

    public function __invoke(UrlInterface $url): RobotsTxt
    {
        if ($this->cache->contains((string) $url)) {
            return $this->cache->get((string) $url);
        }

        $robots = ($this->parser)($url);
        $this->cache = $this->cache->put((string) $url, $robots);

        return $robots;
    }
}
