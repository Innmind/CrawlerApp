<?php
declare(strict_types = 1);

namespace Tests\Crawler\Transport;

use Crawler\Transport\Authentified;
use Innmind\HttpTransport\Transport;
use Innmind\Http\{
    Message\Request\Request,
    Message\Response,
    Message\Method,
    Headers\Headers,
    Header\Header,
    ProtocolVersion,
};
use Innmind\Url\UrlInterface;
use PHPUnit\Framework\TestCase;

class AuthentifiedTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Transport::class,
            new Authentified(
                $this->createMock(Transport::class),
                'apikey'
            )
        );
    }

    public function testFulfill()
    {
        $transport = new Authentified(
            $inner = $this->createMock(Transport::class),
            'apikey'
        );
        $request = new Request(
            $this->createMock(UrlInterface::class),
            $this->createMock(Method::class),
            $this->createMock(ProtocolVersion::class),
            Headers::of(
                new Header('x-foo')
            )
        );
        $inner
            ->expects($this->once())
            ->method('fulfill')
            ->with($this->callback(static function($wrapped) use ($request): bool {
                return $wrapped !== $request &&
                    $wrapped->url() === $request->url() &&
                    $wrapped->method() === $request->method() &&
                    $wrapped->protocolVersion() === $request->protocolVersion() &&
                    (string) $wrapped->headers()->get('x-foo') === 'x-foo: ' &&
                    (string) $wrapped->headers()->get('authorization') === 'Authorization: "Bearer" apikey' &&
                    $wrapped->body() === $request->body();
            }))
            ->willReturn($expected = $this->createMock(Response::class));

        $this->assertSame($expected, $transport->fulfill($request));
    }
}
