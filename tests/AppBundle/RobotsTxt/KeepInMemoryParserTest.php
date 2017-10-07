<?php
declare(strict_types = 1);

namespace Tests\AppBundle\RobotsTxt;

use AppBundle\RobotsTxt\KeepInMemoryParser;
use Innmind\RobotsTxt\{
    Parser,
    RobotsTxt
};
use Innmind\Url\Url;
use PHPUnit\Framework\TestCase;

class KeepInMemoryParserTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Parser::class,
            new KeepInMemoryParser(
                $this->createMock(Parser::class)
            )
        );
    }

    public function testInvokation()
    {
        $parser = new KeepInMemoryParser(
            $inner = $this->createMock(Parser::class)
        );
        $url = Url::fromString('http://example.com');
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($url)
            ->willReturn($expected = $this->createMock(RobotsTxt::class));

        $this->assertSame($expected, $parser($url));
        $this->assertSame($expected, $parser($url));
    }
}
