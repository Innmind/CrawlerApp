<?php
declare(strict_types = 1);

namespace Crawler;

use Innmind\Url\UrlInterface;

/**
 * Makes the app wait a certain time before crawling a resource
 */
interface Delayer
{
    public function __invoke(UrlInterface $url): void;
}
