<?php
declare(strict_types = 1);

namespace AppBundle\Crawler;

use AppBundle\DelayerInterface;
use Innmind\Crawler\{
    CrawlerInterface,
    HttpResource
};
use Innmind\Http\Message\RequestInterface;

final class DelayerAwareCrawler implements CrawlerInterface
{
    private $delay;
    private $crawler;

    public function __construct(
        DelayerInterface $delayer,
        CrawlerInterface $crawler
    ) {
        $this->delay = $delayer;
        $this->crawler = $crawler;
    }

    public function execute(RequestInterface $request): HttpResource
    {
        ($this->delay)($request->url());

        return $this->crawler->execute($request);
    }
}
