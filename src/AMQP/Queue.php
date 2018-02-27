<?php
declare(strict_types = 1);

namespace Crawler\AMQP;

use Innmind\AMQP\Model\Queue\Declaration;

final class Queue
{
    public static function named(string $name): Declaration
    {
        return Declaration::durable()->withName($name);
    }
}
