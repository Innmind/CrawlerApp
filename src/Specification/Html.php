<?php
declare(strict_types = 1);

namespace Crawler\Specification;

use Innmind\Http\Message\Response;

final class Html
{
    public function isSatisfiedBy(Response $response): bool
    {
        if (!$response->headers()->has('content-type')) {
            return false;
        }

        $header = $response
            ->headers()
            ->get('content-type')
            ->values()
            ->join('');

        foreach (['text/html', 'text/xml', 'application/xml', 'application/xhtml'] as $mediaType) {
            if ($header->contains($mediaType)) {
                return true;
            }
        }

        return false;
    }
}
