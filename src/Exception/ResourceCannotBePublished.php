<?php
declare(strict_types = 1);

namespace Crawler\Exception;

use Innmind\Crawler\HttpResource;

final class ResourceCannotBePublished extends RuntimeException
{
    private HttpResource $resource;

    public function __construct(HttpResource $resource)
    {
        $this->resource = $resource;
        parent::__construct();
    }

    public function resource(): HttpResource
    {
        return $this->resource;
    }
}
