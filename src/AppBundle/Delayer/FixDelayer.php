<?php
declare(strict_types = 1);

namespace AppBundle\Delayer;

use AppBundle\{
    Delayer,
    Exception\DomainException
};
use Innmind\Url\UrlInterface;

final class FixDelayer implements Delayer
{
    private $microseconds;

    public function __construct(int $milliseconds)
    {
        if ($milliseconds < 0) {
            throw new DomainException;
        }

        $this->microseconds = $milliseconds * 1000;
    }

    public function __invoke(UrlInterface $url): void
    {
        usleep($this->microseconds);
    }
}
