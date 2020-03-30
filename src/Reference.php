<?php
declare(strict_types = 1);

namespace Crawler;

use Innmind\Rest\Client\Identity;
use Innmind\Url\UrlInterface;

final class Reference
{
    private Identity $identity;
    private string $definition;
    private UrlInterface $server;

    public function __construct(
        Identity $identity,
        string $definition,
        UrlInterface $server
    ) {
        $this->identity = $identity;
        $this->definition = $definition;
        $this->server = $server;
    }

    public function identity(): Identity
    {
        return $this->identity;
    }

    public function definition(): string
    {
        return $this->definition;
    }

    public function server(): UrlInterface
    {
        return $this->server;
    }
}
