<?php
declare(strict_types = 1);

namespace AppBundle\Delayer;

use AppBundle\DelayerInterface;
use Innmind\RobotsTxt\{
    ParserInterface,
    DirectivesInterface,
    Exception\FileNotFoundException
};
use Innmind\Url\{
    UrlInterface,
    Path,
    NullQuery,
    NullFragment
};

final class RobotsTxtAwareDelayer implements DelayerInterface
{
    private $parser;
    private $userAgent;

    public function __construct(ParserInterface $parser, string $userAgent)
    {
        $this->parser = $parser;
        $this->userAgent = $userAgent;
    }

    public function __invoke(UrlInterface $url): void
    {
        try {
            $directives = ($this->parser)(
                $url
                    ->withPath(new Path('/robots.txt'))
                    ->withQuery(new NullQuery)
                    ->withFragment(new NullFragment)
            )
                ->directives()
                ->filter(function(DirectivesInterface $directives): bool {
                    return $directives->targets($this->userAgent) &&
                        $directives->hasCrawlDelay();
                });

            if ($directives->size() === 0) {
                return;
            }

            sleep(
                $directives->reduce(
                    0,
                    function(int $carry, DirectivesInterface $directives): int {
                        return max($carry, $directives->crawlDelay()->toInt());
                    }
                )
            );
        } catch (FileNotFoundException $e) {
            //pass
        }
    }
}
