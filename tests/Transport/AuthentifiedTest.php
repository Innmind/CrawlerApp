<?php
declare(strict_types = 1);

namespace Tests\Crawler\Transport;

use Crawler\Transport\Authentified;
use Innmind\HttpTransport\Transport;
use Innmind\Http\{
    Message\Request\Request,
    Message\Response,
    Message\Method,
    Headers,
    Header\Header,
    ProtocolVersion,
};
use Innmind\Url\Url;
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
        $fulfill = new Authentified(
            $inner = $this->createMock(Transport::class),
            'apikey'
        );
        $request = new Request(
            Url::of('example.com'),
            Method::get(),
            new ProtocolVersion(2, 0),
            Headers::of(
                new Header('x-foo')
            )
        );
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function($wrapped) use ($request): bool {
                return $wrapped !== $request &&
                    $wrapped->url() === $request->url() &&
                    $wrapped->method() === $request->method() &&
                    $wrapped->protocolVersion() === $request->protocolVersion() &&
                    $wrapped->headers()->get('x-foo')->toString() === 'x-foo: ' &&
                    $wrapped->headers()->get('authorization')->toString() === 'Authorization: "Bearer" apikey' &&
                    $wrapped->body() === $request->body();
            }))
            ->willReturn($expected = $this->createMock(Response::class));

        $this->assertSame($expected, $fulfill($request));
    }
}
