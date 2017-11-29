<?php
declare(strict_types = 1);

namespace Tests\AppBundle;

use AppBundle\SameUrlAs;
use Innmind\Url\Url;
use PHPUnit\Framework\TestCase;
use Eris\{
    Generator,
    TestTrait
};

class SameUrlAsTest extends TestCase
{
    use TestTrait;

    public function testSame()
    {
        $this
            ->forAll($this->urls())
            ->then(function(Url $url): void {
                $sameAs = new SameUrlAs(Url::fromString('https://en.wikipedia.org/wiki/H2g2'));

                $this->assertTrue($sameAs($url));
            });
    }

    public function testNotSame()
    {
        $this
            ->forAll($this->urls())
            ->then(function(Url $url): void {
                $sameAs = new SameUrlAs(Url::fromString('https://en.wikipedia.org/'));

                $this->assertFalse($sameAs($url));
            });
    }

    private function urls(): Generator
    {
        return Generator\elements(
            Url::fromString('https://en.wikipedia.org/wiki/H2g2#History'),
            Url::fromString('https://en.wikipedia.org/wiki/H2g2#Terms_and_conditions'),
            Url::fromString('https://en.wikipedia.org/wiki/H2g2#DNA'),
            Url::fromString('https://en.wikipedia.org/wiki/H2g2#See_also'),
            Url::fromString('https://en.wikipedia.org/wiki/H2g2#References'),
            Url::fromString('https://en.wikipedia.org/wiki/H2g2#Further_reading')
        );
    }
}
