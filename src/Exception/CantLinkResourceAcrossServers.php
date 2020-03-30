<?php
declare(strict_types = 1);

namespace Crawler\Exception;

use Crawler\Reference;

final class CantLinkResourceAcrossServers extends RuntimeException
{
    private Reference $source;
    private Reference $target;

    public function __construct(Reference $source, Reference $target)
    {
        $this->source = $source;
        $this->target = $target;
        parent::__construct();
    }

    public function source(): Reference
    {
        return $this->source;
    }

    public function target(): Reference
    {
        return $this->target;
    }
}
