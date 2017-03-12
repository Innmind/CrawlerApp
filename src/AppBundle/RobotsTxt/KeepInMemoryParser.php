<?php
declare(strict_types = 1);

namespace AppBundle\RobotsTxt;

use Innmind\RobotsTxt\{
    ParserInterface,
    RobotsTxtInterface
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\Map;

final class KeepInMemoryParser implements ParserInterface
{
    private $parser;
    private $cache;

    public function __construct(ParserInterface $parser)
    {
        $this->parser = $parser;
        $this->cache = new Map('string', RobotsTxtInterface::class);
    }

    public function __invoke(UrlInterface $url): RobotsTxtInterface
    {
        if ($this->cache->contains((string) $url)) {
            return $this->cache->get((string) $url);
        }

        $robots = ($this->parser)($url);
        $this->cache = $this->cache->put((string) $url, $robots);

        return $robots;
    }
}
