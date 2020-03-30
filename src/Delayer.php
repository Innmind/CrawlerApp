<?php
declare(strict_types = 1);

namespace Crawler;

use Innmind\Url\Url;

/**
 * Makes the app wait a certain time before crawling a resource
 */
interface Delayer
{
    public function __invoke(Url $url): void;
}
