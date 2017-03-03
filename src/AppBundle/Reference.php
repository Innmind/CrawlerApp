<?php
declare(strict_types = 1);

namespace AppBundle;

use Innmind\Rest\Client\{
    IdentityInterface,
    Definition\HttpResource
};

final class Reference
{
    private $identity;
    private $definition;

    public function __construct(
        IdentityInterface $identity,
        HttpResource $definition
    ) {
        $this->identity = $identity;
        $this->definition = $definition;
    }

    public function identity(): IdentityInterface
    {
        return $this->identity;
    }

    public function definition(): HttpResource
    {
        return $this->definition;
    }
}
