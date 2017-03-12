<?php
declare(strict_types = 1);

namespace AppBundle\Crawler;

use AppBundle\Exception\UrlCannotBeCrawledException;
use Innmind\Crawler\{
    CrawlerInterface,
    HttpResource
};
use Innmind\RobotsTxt\{
    ParserInterface,
    Exception\FileNotFoundException
};
use Innmind\Url\{
    NullQuery,
    NullFragment,
    Path
};
use Innmind\Http\Message\RequestInterface;

final class RobotsAwareCrawler implements CrawlerInterface
{
    private $parser;
    private $crawler;
    private $userAgent;

    public function __construct(
        ParserInterface $parser,
        CrawlerInterface $crawler,
        string $userAgent
    ) {
        $this->parser = $parser;
        $this->crawler = $crawler;
        $this->userAgent = $userAgent;
    }

    public function execute(RequestInterface $request): HttpResource
    {
        try {
            $url = $request
                ->url()
                ->withPath(new Path('/robots.txt'))
                ->withQuery(new NullQuery)
                ->withFragment(new NullFragment);
            $robots = ($this->parser)($url);

            if ($robots->disallows($this->userAgent, $request->url())) {
                throw new UrlCannotBeCrawledException($request->url());
            }
        } catch (FileNotFoundException $e) {
            //pass
        }

        return $this->crawler->execute($request);
    }
}
