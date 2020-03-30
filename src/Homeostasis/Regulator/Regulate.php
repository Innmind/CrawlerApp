<?php
declare(strict_types = 1);

namespace Crawler\Homeostasis\Regulator;

use Innmind\IPC\{
    IPC,
    Process\Name,
    Message,
};
use Innmind\Filesystem\MediaType\MediaType;
use Innmind\Immutable\Str;

final class Regulate
{
    private IPC $ipc;
    private Name $name;

    public function __construct(IPC $ipc, Name $name)
    {
        $this->ipc = $ipc;
        $this->name = $name;
    }

    public function __invoke(): void
    {
        $this->ipc->wait($this->name);

        if ($this->ipc->exist($this->name)) {
            $daemon = $this->ipc->get($this->name);
            $daemon->send(new Message\Generic(
                MediaType::fromString('text/plain'),
                Str::of('')
            ));
            $daemon->close();
        }
    }
}
