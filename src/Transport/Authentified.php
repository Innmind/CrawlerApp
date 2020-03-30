<?php
declare(strict_types = 1);

namespace Crawler\Transport;

use Innmind\HttpTransport\Transport;
use Innmind\Http\{
    Message\Request,
    Message\Response,
    Header\Authorization,
    Header\AuthorizationValue,
};

final class Authentified implements Transport
{
    private Transport $fulfill;
    private Authorization $header;

    public function __construct(Transport $fulfill, string $apiKey)
    {
        $this->fulfill = $fulfill;
        $this->header = new Authorization(new AuthorizationValue('Bearer', $apiKey));
    }

    public function __invoke(Request $request): Response
    {
        $request = new Request\Request(
            $request->url(),
            $request->method(),
            $request->protocolVersion(),
            $request->headers()->add($this->header),
            $request->body()
        );

        return ($this->fulfill)($request);
    }
}
