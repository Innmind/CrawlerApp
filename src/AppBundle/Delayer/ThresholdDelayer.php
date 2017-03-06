<?php
declare(strict_types = 1);

namespace AppBundle\Delayer;

use AppBundle\{
    DelayerInterface,
    Exception\InvalidArgumentException
};
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    ElapsedPeriod
};
use Innmind\Url\UrlInterface;

final class ThresholdDelayer implements DelayerInterface
{
    private $attempt;
    private $fallback;
    private $clock;
    private $threshold;

    public function __construct(
        DelayerInterface $attempt,
        DelayerInterface $fallback,
        TimeContinuumInterface $clock,
        int $threshold
    ) {
        if ($threshold < 0) {
            throw new InvalidArgumentException;
        }

        $this->attempt = $attempt;
        $this->fallback = $fallback;
        $this->clock = $clock;
        $this->threshold = new ElapsedPeriod($threshold);
    }

    public function __invoke(UrlInterface $url): void
    {
        $start = $this->clock->now();
        ($this->attempt)($url);
        $waited = $this
            ->clock
            ->now()
            ->elapsedSince($start);

        if ($this->threshold->longerThan($waited)) {
            ($this->fallback)($url);
        }
    }
}
