<?php
declare(strict_types = 1);

namespace Crawler\Crawler;

use Crawler\Exception\UrlCannotBeCrawled;
use Innmind\Crawler\{
    Crawler,
    HttpResource,
};
use Innmind\RobotsTxt\{
    Parser,
    Exception\FileNotFound,
};
use Innmind\Url\Path;
use Innmind\Http\Message\Request;

final class RobotsAwareCrawler implements Crawler
{
    private Parser $parser;
    private Crawler $crawl;
    private string $userAgent;

    public function __construct(
        Parser $parser,
        Crawler $crawl,
        string $userAgent
    ) {
        $this->parser = $parser;
        $this->crawl = $crawl;
        $this->userAgent = $userAgent;
    }

    public function __invoke(Request $request): HttpResource
    {
        try {
            $url = $request
                ->url()
                ->withPath(Path::of('/robots.txt'))
                ->withoutQuery()
                ->withoutFragment();
            $robots = ($this->parser)($url);

            if ($robots->disallows($this->userAgent, $request->url())) {
                throw new UrlCannotBeCrawled($request->url());
            }
        } catch (FileNotFound $e) {
            //pass
        }

        return ($this->crawl)($request);
    }
}
