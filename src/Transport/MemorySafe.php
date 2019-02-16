<?php
declare(strict_types = 1);

namespace Crawler\Transport;

use Crawler\{
    Specification\Html,
    Exception\ResponseTooHeavy,
};
use Innmind\HttpTransport\Transport;
use Innmind\Http\Message\{
    Request,
    Response
};

final class MemorySafe implements Transport
{
    private $fulfill;
    private $threshold;

    public function __construct(Transport $fulfill, int $threshold = null)
    {
        $this->fulfill = $fulfill;
        $this->threshold = $threshold ?? 1048576; // 1MB
    }

    public function __invoke(Request $request): Response
    {
        $response = ($this->fulfill)($request);

        if (
            $response->body()->size()->toInt() > $this->threshold &&
            (new Html)->isSatisfiedBy($response)
        ) {
            // in case the payload is too heavy it will result in a too high
            // object structure when parsing the html that may result in an
            // out of memory fatal error
            throw new ResponseTooHeavy;
        }

        return $response;
    }
}
