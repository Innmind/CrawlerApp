<?php
declare(strict_types = 1);

namespace Crawler\Specification;

use Innmind\Http\Message\Response;
use function Innmind\Immutable\join;

final class Html
{
    public function isSatisfiedBy(Response $response): bool
    {
        if (!$response->headers()->contains('content-type')) {
            return false;
        }

        $header = join(
            '',
            $response
                ->headers()
                ->get('content-type')
                ->values()
                ->mapTo(
                    'string',
                    static fn($value): string => $value->toString(),
                ),
        );

        foreach (['text/html', 'text/xml', 'application/xml', 'application/xhtml'] as $mediaType) {
            if ($header->contains($mediaType)) {
                return true;
            }
        }

        return false;
    }
}
