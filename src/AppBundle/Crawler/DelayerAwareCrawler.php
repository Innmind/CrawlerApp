<?php
declare(strict_types = 1);

namespace AppBundle\Crawler;

use AppBundle\DelayerInterface;
use Innmind\Crawler\{
    Crawler,
    HttpResource
};
use Innmind\Http\Message\Request;

final class DelayerAwareCrawler implements Crawler
{
    private $delay;
    private $crawler;

    public function __construct(
        DelayerInterface $delayer,
        Crawler $crawler
    ) {
        $this->delay = $delayer;
        $this->crawler = $crawler;
    }

    public function execute(Request $request): HttpResource
    {
        ($this->delay)($request->url());

        return $this->crawler->execute($request);
    }
}
