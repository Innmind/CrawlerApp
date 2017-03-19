<?php
declare(strict_types = 1);

namespace AppBundle\Exception;

use AppBundle\Reference;

final class CantLinkResourceAcrossServersException extends RuntimeException
{
    private $source;
    private $target;

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
