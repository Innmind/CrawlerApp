<?php
declare(strict_types = 1);

namespace Crawler\Exception;

use Innmind\Url\Url;

final class UrlCannotBeCrawled extends DomainException
{
    private Url $url;

    public function __construct(Url $url)
    {
        $this->url = $url;
    }

    public function url(): Url
    {
        return $this->url;
    }
}
