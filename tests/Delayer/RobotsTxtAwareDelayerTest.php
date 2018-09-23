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
use Innmind\Url\{
    Url,
    UrlInterface,
};
use Innmind\TimeWarp\Halt;
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    Period\Earth\Second,
};
use Innmind\Immutable\Str;
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
                $this->createMock(TimeContinuumInterface::class)
            )
        );
    }

    public function testDoesntWaitWhenNoRobots()
    {
        $delayer = new RobotsTxtAwareDelayer(
            $parser = $this->createMock(Parser::class),
            'foo',
            $this->createMock(Halt::class),
            $this->createMock(TimeContinuumInterface::class)
        );
        $parser
            ->expects($this->once())
            ->method('__invoke')
            ->will($this->throwException(new FileNotFound));

        $start = microtime(true);
        $this->assertNull($delayer(Url::fromString('http://example.com/')));
        $this->assertTrue(microtime(true) - $start < 1);
    }

    public function testDoesntWaitWhenNoDirectivesApply()
    {
        $delayer = new RobotsTxtAwareDelayer(
            $parser = $this->createMock(Parser::class),
            'bar',
            $this->createMock(Halt::class),
            $this->createMock(TimeContinuumInterface::class)
        );
        $parser
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function(UrlInterface $url): bool {
                return (string) $url === 'http://example.com/robots.txt';
            }))
            ->willReturn(
                new RobotsTxt(
                    $this->createMock(UrlInterface::class),
                    (new Walker)(new Str(<<<TXT
User-agent: foo
Crawl-delay: 10
TXT
                    ))
                )
            );

        $start = microtime(true);
        $this->assertNull($delayer(Url::fromString('http://example.com/')));
        $this->assertTrue(microtime(true) - $start < 1);
    }

    public function testDoesntWaitAsSpecifiedByRobotsTxt()
    {
        $delayer = new RobotsTxtAwareDelayer(
            $parser = $this->createMock(Parser::class),
            'foo',
            $this->createMock(Halt::class),
            $this->createMock(TimeContinuumInterface::class)
        );
        $parser
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function(UrlInterface $url): bool {
                return (string) $url === 'http://example.com/robots.txt';
            }))
            ->willReturn(
                new RobotsTxt(
                    $this->createMock(UrlInterface::class),
                    (new Walker)(new Str(<<<TXT
User-agent: foo
Crawl-delay: 0
TXT
                    ))
                )
            );

        $start = microtime(true);
        $this->assertNull($delayer(Url::fromString('http://example.com/')));
        $this->assertTrue(microtime(true) - $start < 1);
    }

    public function testWait()
    {
        $delayer = new RobotsTxtAwareDelayer(
            $parser = $this->createMock(Parser::class),
            'foo',
            $halt = $this->createMock(Halt::class),
            $clock = $this->createMock(TimeContinuumInterface::class)
        );
        $parser
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function(UrlInterface $url): bool {
                return (string) $url === 'http://example.com/robots.txt';
            }))
            ->willReturn(
                new RobotsTxt(
                    $this->createMock(UrlInterface::class),
                    (new Walker)(new Str(<<<TXT
User-agent: foo
Crawl-delay: 2
TXT
                    ))
                )
            );
        $halt
            ->expects($this->once())
            ->method('__invoke')
            ->with($clock, new Second(2));

        $this->assertNull($delayer(Url::fromString('http://example.com/')));
    }

    public function testWaitTheLongestOfMatchingCrawlDelays()
    {
        $delayer = new RobotsTxtAwareDelayer(
            $parser = $this->createMock(Parser::class),
            'foo',
            $halt = $this->createMock(Halt::class),
            $clock = $this->createMock(TimeContinuumInterface::class)
        );
        $parser
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function(UrlInterface $url): bool {
                return (string) $url === 'http://example.com/robots.txt';
            }))
            ->willReturn(
                new RobotsTxt(
                    $this->createMock(UrlInterface::class),
                    (new Walker)(new Str(<<<TXT
User-agent: foo
Crawl-delay: 2

User-agent: *
Crawl-delay: 4
TXT
                    ))
                )
            );
        $halt
            ->expects($this->once())
            ->method('__invoke')
            ->with($clock, new Second(4));

        $this->assertNull($delayer(Url::fromString('http://example.com/')));
    }
}
