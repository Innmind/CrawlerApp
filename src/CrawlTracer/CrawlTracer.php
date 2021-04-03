<?php
declare(strict_types = 1);

namespace Crawler\CrawlTracer;

use Crawler\{
    CrawlTracer as CrawlTracerInterface,
    Exception\HostNeverHit,
};
use Innmind\Filesystem\{
    Adapter,
    Directory\Directory,
    File\File,
    Stream\NullStream,
    Name,
};
use Innmind\Stream\Readable\Stream;
use Innmind\TimeContinuum\{
    Clock,
    PointInTime,
    Earth\Format\ISO8601,
};
use Innmind\Url\{
    Url,
    Authority\Host,
};

final class CrawlTracer implements CrawlTracerInterface
{
    private const URLS = 'urls.txt';
    private const HITS = 'hits';

    private Adapter $filesystem;
    private Clock $clock;

    public function __construct(
        Adapter $filesystem,
        Clock $clock
    ) {
        $this->filesystem = $filesystem;
        $this->clock = $clock;

        if (!$filesystem->contains(new Name(self::HITS))) {
            $filesystem->add(Directory::named(self::HITS));
        }

        if (!$filesystem->contains(new Name(self::URLS))) {
            $filesystem->add(
                File::named(
                    self::URLS,
                    new NullStream
                )
            );
        }
    }

    public function trace(Url $url): CrawlTracerInterface
    {
        /** @var Directory */
        $hits = $this->filesystem->get(new Name(self::HITS));
        $this->filesystem->add(
            $hits->add(
                new File(
                    $this->name($url->authority()->host()),
                    Stream::ofContent(
                        $this->clock->now()->format(new ISO8601)
                    )
                )
            )
        );

        if ($this->knows($url)) {
            return $this;
        }

        $file = $this->filesystem->get(new Name(self::URLS));

        $this->filesystem->add(
            File::named(
                self::URLS,
                Stream::ofContent(
                    $file->content()->toString().$url->withoutFragment()->toString()."\n"
                )
            )
        );

        return $this;
    }

    public function knows(Url $url): bool
    {
        $urls = $this
            ->filesystem
            ->get(new Name(self::URLS))
            ->content()
            ->read();

        return $urls->contains($url->withoutFragment()->toString());
    }

    public function lastHit(Host $host): PointInTime
    {
        $name = $this->name($host);
        /** @var Directory */
        $directory = $this->filesystem->get(new Name(self::HITS));

        if (!$directory->contains($name)) {
            throw new HostNeverHit;
        }

        return $this->clock->at(
            $directory
                ->get($name)
                ->content()
                ->toString()
        );
    }

    private function name(Host $host): Name
    {
        return new Name($host->toString().'.txt');
    }
}
