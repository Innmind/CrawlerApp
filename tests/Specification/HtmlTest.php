<?php
declare(strict_types = 1);

namespace Tests\Crawler\Specification;

use Crawler\Specification\Html;
use Innmind\Http\{
    Message\Response,
    Headers,
    Header\ContentType,
    Header\ContentTypeValue,
};
use PHPUnit\Framework\TestCase;

class HtmlTest extends TestCase
{
    public function testNotSatisfiedWhenNoContentType()
    {
        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(Headers::of());

        $this->assertFalse((new Html)->isSatisfiedBy($response));
    }

    public function testNotSatisfiedWhenInvalidContentType()
    {
        $response = $this->createMock(Response::class);
        $response
            ->expects($this->exactly(2))
            ->method('headers')
            ->willReturn(Headers::of(
                new ContentType(
                    new ContentTypeValue('image', 'png')
                )
            ));

        $this->assertFalse((new Html)->isSatisfiedBy($response));
    }

    /**
     * @dataProvider cases
     */
    public function testSatisfiedWhenValidContentType($type, $subType)
    {
        $response = $this->createMock(Response::class);
        $response
            ->expects($this->exactly(2))
            ->method('headers')
            ->willReturn(Headers::of(
                new ContentType(
                    new ContentTypeValue($type, $subType)
                )
            ));

        $this->assertTrue((new Html)->isSatisfiedBy($response));
    }

    public function cases()
    {
        return [
            ['text', 'html'],
            ['text', 'xml'],
            ['application', 'xml'],
            ['application', 'xhtml'],
        ];
    }
}
