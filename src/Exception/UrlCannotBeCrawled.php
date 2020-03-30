<?php
declare(strict_types = 1);

namespace Crawler\Exception;

use Innmind\Url\UrlInterface;

final class UrlCannotBeCrawled extends DomainException
{
    private UrlInterface $url;

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
