<?php
declare(strict_types = 1);

namespace AppBundle\Exception;

use Innmind\Crawler\HttpResource;

final class ResourceCannotBePublished extends RuntimeException
{
    private $resource;

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
