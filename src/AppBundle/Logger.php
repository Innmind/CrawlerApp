<?php
declare(strict_types = 1);

namespace AppBundle;

use Monolog\{
    Logger as Monolog,
    Handler\HandlerInterface,
};

final class Logger
{
    public static function build(string $name, HandlerInterface $handler): Monolog
    {
        return new Monolog($name, [$handler]);
    }
}
