<?php
declare(strict_types = 1);

namespace Crawler\RobotsTxt;

use Innmind\RobotsTxt\{
    Parser,
    RobotsTxt,
};
use Innmind\Url\Url;
use Innmind\Immutable\Map;

final class KeepInMemoryParser implements Parser
{
    private Parser $parser;
    /** @var Map<string, RobotsTxt> */
    private Map $cache;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
        /** @var Map<string, RobotsTxt> */
        $this->cache = Map::of('string', RobotsTxt::class);
    }

    public function __invoke(Url $url): RobotsTxt
    {
        if ($this->cache->contains($url->toString())) {
            return $this->cache->get($url->toString());
        }

        $robots = ($this->parser)($url);
        $this->cache = $this->cache->put($url->toString(), $robots);

        return $robots;
    }
}
