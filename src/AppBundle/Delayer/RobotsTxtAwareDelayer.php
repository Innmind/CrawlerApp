<?php
declare(strict_types = 1);

namespace AppBundle\Delayer;

use AppBundle\Delayer;
use Innmind\RobotsTxt\{
    Parser,
    Directives,
    Exception\FileNotFound
};
use Innmind\Url\{
    UrlInterface,
    Path,
    NullQuery,
    NullFragment
};

final class RobotsTxtAwareDelayer implements Delayer
{
    private $parser;
    private $userAgent;

    public function __construct(Parser $parser, string $userAgent)
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
                ->filter(function(Directives $directives): bool {
                    return $directives->targets($this->userAgent) &&
                        $directives->hasCrawlDelay();
                });

            if ($directives->size() === 0) {
                return;
            }

            sleep(
                $directives->reduce(
                    0,
                    function(int $carry, Directives $directives): int {
                        return max($carry, $directives->crawlDelay()->toInt());
                    }
                )
            );
        } catch (FileNotFound $e) {
            //pass
        }
    }
}
