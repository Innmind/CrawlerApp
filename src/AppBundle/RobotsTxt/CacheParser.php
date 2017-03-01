<?php
declare(strict_types = 1);

namespace AppBundle\RobotsTxt;

use Innmind\RobotsTxt\{
    ParserInterface,
    RobotsTxtInterface,
    RobotsTxt,
    Parser\Walker
};
use Innmind\Filesystem\{
    AdapterInterface,
    File,
    Stream\StringStream
};
use Innmind\Url\{
    UrlInterface,
    NullScheme,
    NullPath,
    Authority\NullUserInformation
};
use Innmind\Immutable\Str;

final class CacheParser implements ParserInterface
{
    private $parser;
    private $walker;
    private $filesystem;

    public function __construct(
        ParserInterface $parser,
        Walker $walker,
        AdapterInterface $filesystem
    ) {
        $this->parser = $parser;
        $this->walker = $walker;
        $this->filesystem = $filesystem;
    }

    public function __invoke(UrlInterface $url): RobotsTxtInterface
    {
        $name = $this->name($url);

        if ($this->filesystem->has($name)) {
            $directives = ($this->walker)(new Str(
                (string) $this
                    ->filesystem
                    ->get($name)
                    ->content()
            ));

            return new RobotsTxt($url, $directives);
        }

        $robots = ($this->parser)($url);
        $this->filesystem->add(
            new File(
                $name,
                new StringStream((string) $robots)
            )
        );

        return $robots;
    }

    private function name(UrlInterface $url): string
    {
        $name = (string) $url
            ->withScheme(new NullScheme)
            ->withAuthority(
                $url
                    ->authority()
                    ->withUserInformation(new NullUserInformation)
            )
            ->withPath(new NullPath);

        return rtrim($name, '/').'.txt';
    }
}
