<?php
declare(strict_types = 1);

namespace Tests\AppBundle\RobotsTxt;

use AppBundle\RobotsTxt\KeepInMemoryParser;
use Innmind\RobotsTxt\{
    ParserInterface,
    RobotsTxtInterface
};
use Innmind\Url\Url;
use PHPUnit\Framework\TestCase;

class KeepInMemoryParserTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            ParserInterface::class,
            new KeepInMemoryParser(
                $this->createMock(ParserInterface::class)
            )
        );
    }

    public function testInvokation()
    {
        $parser = new KeepInMemoryParser(
            $inner = $this->createMock(ParserInterface::class)
        );
        $url = Url::fromString('http://example.com');
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($url)
            ->willReturn($expected = $this->createMock(RobotsTxtInterface::class));

        $this->assertSame($expected, $parser($url));
        $this->assertSame($expected, $parser($url));
    }
}
