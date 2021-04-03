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
    Url,
    Path,
};
use Innmind\TimeWarp\Halt;
use Innmind\TimeContinuum\{
    Clock,
    Earth\Period\Second,
};

final class RobotsTxtAwareDelayer implements Delayer
{
    private Parser $parser;
    private string $userAgent;
    private Halt $halt;
    private Clock $clock;

    public function __construct(
        Parser $parser,
        string $userAgent,
        Halt $halt,
        Clock $clock
    ) {
        $this->parser = $parser;
        $this->userAgent = $userAgent;
        $this->halt = $halt;
        $this->clock = $clock;
    }

    public function __invoke(Url $url): void
    {
        try {
            $directives = ($this->parser)(
                $url
                    ->withPath(Path::of('/robots.txt'))
                    ->withoutQuery()
                    ->withoutFragment()
            )
                ->directives()
                ->filter(function(Directives $directives): bool {
                    return $directives->targets($this->userAgent) &&
                        $directives->hasCrawlDelay();
                });

            if ($directives->empty()) {
                return;
            }

            $period = new Second(
                $directives->reduce(
                    0,
                    static function(int $carry, Directives $directives): int {
                        return \max($carry, $directives->crawlDelay()->toInt());
                    }
                )
            );
            ($this->halt)($this->clock, $period);
        } catch (FileNotFound $e) {
            //pass
        }
    }
}
