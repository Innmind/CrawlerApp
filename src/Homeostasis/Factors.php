<?php
declare(strict_types = 1);

namespace Crawler\Homeostasis;

use Innmind\Homeostasis\{
    Factor\Cpu,
    Factor\Log,
    Sensor\Measure\Weight,
};
use Innmind\Server\Status\Server;
use Innmind\TimeContinuum\TimeContinuumInterface;
use Innmind\Math\{
    Algebra\Number\Number,
    Polynom\Polynom,
};
use Innmind\Filesystem\Adapter;
use Innmind\LogReader\Reader;
use Innmind\Immutable\Set;

final class Factors
{
    public static function cpu(
        TimeContinuumInterface $clock,
        Server $server
    ): Cpu {
        return new Cpu(
            $clock,
            $server,
            new Weight(Number::wrap(0.7)),
            (new Polynom(Number::wrap(-0.0012195890835040666)))
                ->withDegree(Number::wrap(1), Number::wrap(2.0996410102652))
                ->withDegree(Number::wrap(2), Number::wrap(-17.27684076838))
                ->withDegree(Number::wrap(3), Number::wrap(86.261146237871))
                ->withDegree(Number::wrap(4), Number::wrap(-189.7736029403))
                ->withDegree(Number::wrap(5), Number::wrap(184.66906744449))
                ->withDegree(Number::wrap(6), Number::wrap(-64.975065630889))
        );
    }

    public static function log(
        TimeContinuumInterface $clock,
        Reader $reader,
        Adapter $logs
    ): Log {
        return new Log(
            $clock,
            $reader,
            $logs,
            new Weight(Number::wrap(0.3)),
            (new Polynom(Number::wrap(-5.03E-8)))
                ->withDegree(Number::wrap(1), Number::wrap(12.4035))
                ->withDegree(Number::wrap(2), Number::wrap(-52.3392))
                ->withDegree(Number::wrap(3), Number::wrap(87.7193))
                ->withDegree(Number::wrap(4), Number::wrap(-46.7836)),
            static function(Log $line): bool {
                return $line->attributes()->contains('level') &&
                    Set::of('string', 'emergency', 'alert', 'critical', 'error')->contains(
                        $line->attributes()->get('level')->value()
                    );
            },
            'crawler'
        );
    }
}
