<?php
declare(strict_types = 1);

namespace Tests\Crawler\Delayer;

use Crawler\{
    Delayer\RobotsTxtAwareDelayer,
    Delayer,
};
use Innmind\RobotsTxt\{
    Parser,
    RobotsTxt\RobotsTxt,
    Parser\Walker,
    Exception\FileNotFound,
};
use Innmind\Url\Url;
use Innmind\TimeWarp\Halt;
use Innmind\TimeContinuum\{
    Clock,
    Earth\Period\Second,
};
use Innmind\Immutable\{
    Str,
    Sequence,
};
use PHPUnit\Framework\TestCase;

class RobotsTxtAwareDelayerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Delayer::class,
            new RobotsTxtAwareDelayer(
                $this->createMock(Parser::class),
                'foo',
                $this->createMock(Halt::class),
                $this->createMock(Clock::class)
            )
        );
    }

    public function testDoesntWaitWhenNoRobots()
    {
        $delayer = new RobotsTxtAwareDelayer(
            $parser = $this->createMock(Parser::class),
            'foo',
            $this->createMock(Halt::class),
            $this->createMock(Clock::class)
        );
        $parser
            ->expects($this->once())
            ->method('__invoke')
            ->will($this->throwException(new FileNotFound));

        $start = \microtime(true);
        $this->assertNull($delayer(Url::of('http://example.com/')));
        $this->assertTrue(\microtime(true) - $start < 1);
    }

    public function testDoesntWaitWhenNoDirectivesApply()
    {
        $delayer = new RobotsTxtAwareDelayer(
            $parser = $this->createMock(Parser::class),
            'bar',
            $this->createMock(Halt::class),
            $this->createMock(Clock::class)
        );
        $parser
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function(Url $url): bool {
                return $url->toString() === 'http://example.com/robots.txt';
            }))
            ->willReturn(
                new RobotsTxt(
                    Url::of('example.com'),
                    (new Walker)(Sequence::of(
                        Str::class,
                        Str::of('User-agent: foo'),
                        Str::of('Crawl-delay: 10'),
                    ))
                )
            );

        $start = \microtime(true);
        $this->assertNull($delayer(Url::of('http://example.com/')));
        $this->assertTrue(\microtime(true) - $start < 1);
    }

    public function testDoesntWaitAsSpecifiedByRobotsTxt()
    {
        $delayer = new RobotsTxtAwareDelayer(
            $parser = $this->createMock(Parser::class),
            'foo',
            $this->createMock(Halt::class),
            $this->createMock(Clock::class)
        );
        $parser
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function(Url $url): bool {
                return $url->toString() === 'http://example.com/robots.txt';
            }))
            ->willReturn(
                new RobotsTxt(
                    Url::of('example.com'),
                    (new Walker)(Sequence::of(
                        Str::class,
                        Str::of('User-agent: foo'),
                        Str::of('Crawl-delay: 0'),
                    ))
                )
            );

        $start = \microtime(true);
        $this->assertNull($delayer(Url::of('http://example.com/')));
        $this->assertTrue(\microtime(true) - $start < 1);
    }

    public function testWait()
    {
        $delayer = new RobotsTxtAwareDelayer(
            $parser = $this->createMock(Parser::class),
            'foo',
            $halt = $this->createMock(Halt::class),
            $clock = $this->createMock(Clock::class)
        );
        $parser
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function(Url $url): bool {
                return $url->toString() === 'http://example.com/robots.txt';
            }))
            ->willReturn(
                new RobotsTxt(
                    Url::of('example.com'),
                    (new Walker)(Sequence::of(
                        Str::class,
                        Str::of('User-agent: foo'),
                        Str::of('Crawl-delay: 2'),
                    ))
                )
            );
        $halt
            ->expects($this->once())
            ->method('__invoke')
            ->with($clock, new Second(2));

        $this->assertNull($delayer(Url::of('http://example.com/')));
    }

    public function testWaitTheLongestOfMatchingCrawlDelays()
    {
        $delayer = new RobotsTxtAwareDelayer(
            $parser = $this->createMock(Parser::class),
            'foo',
            $halt = $this->createMock(Halt::class),
            $clock = $this->createMock(Clock::class)
        );
        $parser
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function(Url $url): bool {
                return $url->toString() === 'http://example.com/robots.txt';
            }))
            ->willReturn(
                new RobotsTxt(
                    Url::of('example.com'),
                    (new Walker)(Sequence::of(
                        Str::class,
                        Str::of('User-agent: foo'),
                        Str::of('Crawl-delay: 2'),
                        Str::of(''),
                        Str::of('User-agent: *'),
                        Str::of('Crawl-delay: 4'),
                    ))
                )
            );
        $halt
            ->expects($this->once())
            ->method('__invoke')
            ->with($clock, new Second(4));

        $this->assertNull($delayer(Url::of('http://example.com/')));
    }
}
