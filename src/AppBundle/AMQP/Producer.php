<?php
declare(strict_types = 1);

namespace AppBundle\AMQP;

use Innmind\AMQP\{
    Producers,
    Producer as ProducerInterface,
};

final class Producer
{
    public static function get(Producers $producers, string $exchange): ProducerInterface
    {
        return $producers->get($exchange);
    }
}
