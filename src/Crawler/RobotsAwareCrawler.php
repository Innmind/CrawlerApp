<?php
declare(strict_types = 1);

namespace Crawler\Crawler;

use Crawler\Exception\UrlCannotBeCrawled;
use Innmind\Crawler\{
    Crawler,
    HttpResource
};
use Innmind\RobotsTxt\{
    Parser,
    Exception\FileNotFound
};
use Innmind\Url\{
    NullQuery,
    NullFragment,
    Path
};
use Innmind\Http\Message\Request;

final class RobotsAwareCrawler implements Crawler
{
    private $parser;
    private $crawl;
    private $userAgent;

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
                ->withPath(new Path('/robots.txt'))
                ->withQuery(new NullQuery)
                ->withFragment(new NullFragment);
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
