<?php
declare(strict_types = 1);

namespace Tests\AppBundle\Delayer;

use AppBundle\{
    Delayer\RobotsTxtAwareDelayer,
    DelayerInterface
};
use Innmind\RobotsTxt\{
    ParserInterface,
    RobotsTxt,
    Parser\Walker,
    Exception\FileNotFoundException
};
use Innmind\Url\{
    Url,
    UrlInterface
};
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class RobotsTxtAwareDelayerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            DelayerInterface::class,
            new RobotsTxtAwareDelayer(
                $this->createMock(ParserInterface::class),
                'foo'
            )
        );
    }

    public function testDoesntWaitWhenNoRobots()
    {
        $delayer = new RobotsTxtAwareDelayer(
            $parser = $this->createMock(ParserInterface::class),
            'foo'
        );
        $parser
            ->expects($this->once())
            ->method('__invoke')
            ->will($this->throwException(new FileNotFoundException));

        $start = microtime(true);
        $this->assertNull($delayer(Url::fromString('http://example.com/')));
        $this->assertTrue(microtime(true) - $start < 1);
    }

    public function testDoesntWaitWhenNoDirectivesApply()
    {
        $delayer = new RobotsTxtAwareDelayer(
            $parser = $this->createMock(ParserInterface::class),
            'bar'
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
Crawl-delay: 100
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
            $parser = $this->createMock(ParserInterface::class),
            'foo'
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

        $start = microtime(true);
        $this->assertNull($delayer(Url::fromString('http://example.com/')));
        $this->assertTrue(microtime(true) - $start >= 4);
    }
}
