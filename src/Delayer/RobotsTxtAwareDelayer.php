<?php
declare(strict_types = 1);

namespace Crawler\Delayer;

use Crawler\Delayer;
use Innmind\RobotsTxt\{
    Parser,
    Directives,
    Exception\FileNotFound,
};
use Innmind\Url\{
    UrlInterface,
    Path,
    NullQuery,
    NullFragment,
};
use Innmind\TimeWarp\Halt;
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    Period\Earth\Second,
};

final class RobotsTxtAwareDelayer implements Delayer
{
    private $parser;
    private $userAgent;
    private $halt;
    private $clock;

    public function __construct(
        Parser $parser,
        string $userAgent,
        Halt $halt,
        TimeContinuumInterface $clock
    ) {
        $this->parser = $parser;
        $this->userAgent = $userAgent;
        $this->halt = $halt;
        $this->clock = $clock;
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

            $period = new Second(
                $directives->reduce(
                    0,
                    function(int $carry, Directives $directives): int {
                        return max($carry, $directives->crawlDelay()->toInt());
                    }
                )
            );
            ($this->halt)($this->clock, $period);
        } catch (FileNotFound $e) {
            //pass
        }
    }
}
