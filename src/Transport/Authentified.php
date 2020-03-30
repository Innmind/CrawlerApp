<?php
declare(strict_types = 1);

namespace Crawler\Transport;

use Innmind\HttpTransport\Transport;
use Innmind\Http\{
    Message\Request,
    Message\Response,
    Headers\Headers,
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
        $headers = \iterator_to_array($request->headers());
        $headers = \array_values($headers);
        $headers[] = $this->header;

        $request = new Request\Request(
            $request->url(),
            $request->method(),
            $request->protocolVersion(),
            Headers::of(...$headers),
            $request->body()
        );

        return ($this->fulfill)($request);
    }
}
