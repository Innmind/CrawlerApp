<?php
declare(strict_types = 1);

namespace AppBundle;

use Innmind\Url\UrlInterface;

/**
 * Makes the app wait a certain time before crawling a resource
 */
interface DelayerInterface
{
    public function __invoke(UrlInterface $url): void;
}
