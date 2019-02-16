<?php
declare(strict_types = 1);

namespace Tests\Crawler\Transport;

use Crawler\{
    Transport\MemorySafe,
    Exception\ResponseTooHeavy,
};
use Innmind\HttpTransport\Transport;
use Innmind\Http\{
    Message\Request,
    Message\Response,
    Headers\Headers,
    Header\ContentType,
    Header\ContentTypeValue,
};
use Innmind\Stream\{
    Readable,
    Stream\Size,
};
use PHPUnit\Framework\TestCase;

class MemorySafeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Transport::class,
            new MemorySafe(
                $this->createMock(Transport::class)
            )
        );
    }

    public function testDoesntThrowWhenNotOverThreshold()
    {
        $fulfill = new MemorySafe(
            $inner = $this->createMock(Transport::class)
        );
        $request = $this->createMock(Request::class);
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn($body = $this->createMock(Readable::class));
        $body
            ->expects($this->once())
            ->method('size')
            ->willReturn(new Size(1024*1024)); // 1MB

        $this->assertSame($response, $fulfill($request));
    }

    public function testDoesntThrowWhenOverThresholdButNotHtml()
    {
        $fulfill = new MemorySafe(
            $inner = $this->createMock(Transport::class)
        );
        $request = $this->createMock(Request::class);
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn($body = $this->createMock(Readable::class));
        $body
            ->expects($this->once())
            ->method('size')
            ->willReturn(new Size(1024*1024 + 1)); // >1MB
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(Headers::of());

        $this->assertSame($response, $fulfill($request));
    }

    public function testDoesntThrowWhenHtmlNotOverThreshold()
    {
        $fulfill = new MemorySafe(
            $inner = $this->createMock(Transport::class)
        );
        $request = $this->createMock(Request::class);
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn($body = $this->createMock(Readable::class));
        $body
            ->expects($this->once())
            ->method('size')
            ->willReturn(new Size(1024*1024)); // >1MB
        $response
            ->expects($this->any())
            ->method('headers')
            ->willReturn(Headers::of(
                new ContentType(
                    new ContentTypeValue('text', 'html')
                )
            ));

        $this->assertSame($response, $fulfill($request));
    }

    public function testThrowWhenOverThresholdAndHtml()
    {
        $fulfill = new MemorySafe(
            $inner = $this->createMock(Transport::class)
        );
        $request = $this->createMock(Request::class);
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ->willReturn($response = $this->createMock(Response::class));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn($body = $this->createMock(Readable::class));
        $body
            ->expects($this->once())
            ->method('size')
            ->willReturn(new Size(1024*1024 + 1)); // >1MB
        $response
            ->expects($this->any())
            ->method('headers')
            ->willReturn(Headers::of(
                new ContentType(
                    new ContentTypeValue('text', 'html')
                )
            ));

        $this->expectException(ResponseTooHeavy::class);

        $fulfill($request);
    }
}
