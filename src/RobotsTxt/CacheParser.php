<?php
declare(strict_types = 1);

namespace Crawler\RobotsTxt;

use Innmind\RobotsTxt\{
    Parser,
    RobotsTxt,
    Parser\Walker,
};
use Innmind\Filesystem\{
    Adapter,
    File\File,
    Name,
};
use Innmind\Stream\Readable\Stream;
use Innmind\Url\Url;
use Innmind\Immutable\Str;

final class CacheParser implements Parser
{
    private Parser $parser;
    private Walker $walker;
    private Adapter $filesystem;

    public function __construct(
        Parser $parser,
        Walker $walker,
        Adapter $filesystem
    ) {
        $this->parser = $parser;
        $this->walker = $walker;
        $this->filesystem = $filesystem;
    }

    public function __invoke(Url $url): RobotsTxt
    {
        $name = $this->name($url);

        if ($this->filesystem->contains($name)) {
            $directives = ($this->walker)(
                $this
                    ->filesystem
                    ->get($name)
                    ->content()
                    ->read()
                    ->split("\n")
            );

            return new RobotsTxt\RobotsTxt($url, $directives);
        }

        $robots = ($this->parser)($url);
        $this->filesystem->add(
            new File(
                $name,
                Stream::ofContent($robots->toString())
            )
        );

        return $robots;
    }

    private function name(Url $url): Name
    {
        $name = $url
            ->withoutScheme()
            ->withAuthority(
                $url
                    ->authority()
                    ->withoutUserInformation()
            )
            ->withoutPath()
            ->toString();

        return new Name(\rtrim($name, '/').'.txt');
    }
}
