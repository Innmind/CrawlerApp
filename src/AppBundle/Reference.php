<?php
declare(strict_types = 1);

namespace AppBundle;

use Innmind\Rest\Client\IdentityInterface;
use Innmind\Url\UrlInterface;

final class Reference
{
    private $identity;
    private $definition;
    private $server;

    public function __construct(
        IdentityInterface $identity,
        string $definition,
        UrlInterface $server
    ) {
        $this->identity = $identity;
        $this->definition = $definition;
        $this->server = $server;
    }

    public function identity(): IdentityInterface
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
