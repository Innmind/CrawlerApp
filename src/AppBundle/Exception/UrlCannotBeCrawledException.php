<?php
declare(strict_types = 1);

namespace AppBundle\Exception;

use Innmind\Url\UrlInterface;

final class UrlCannotBeCrawledException extends DomainException
{
    private $url;

    public function __construct(UrlInterface $url)
    {
        $this->url = $url;
        parent::__construct();
    }

    public function url(): UrlInterface
    {
        return $this->url;
    }
}
