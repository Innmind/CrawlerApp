<?php
declare(strict_types = 1);

namespace AppBundle\CrawlTracer;

use AppBundle\{
    CrawlTracerInterface,
    Exception\HostNeverHitException
};
use Innmind\Filesystem\{
    AdapterInterface,
    Directory,
    File,
    Stream\StringStream,
    Stream\NullStream
};
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    PointInTimeInterface,
    Format\ISO8601
};
use Innmind\Url\{
    UrlInterface,
    Authority\HostInterface
};
use Innmind\Immutable\{
    Str,
    Exception\SubstringException
};

final class CrawlTracer implements CrawlTracerInterface
{
    private const URLS = 'urls.txt';
    private const HITS = 'hits';

    private $filesystem;
    private $clock;

    public function __construct(
        AdapterInterface $filesystem,
        TimeContinuumInterface $clock
    ) {
        $this->filesystem = $filesystem;
        $this->clock = $clock;

        if (!$filesystem->has(self::HITS)) {
            $filesystem->add(new Directory(self::HITS));
        }

        if (!$filesystem->has(self::URLS)) {
            $filesystem->add(
                new File(
                    self::URLS,
                    new NullStream
                )
            );
        }
    }

    public function trace(UrlInterface $url): CrawlTracerInterface
    {
        $this->filesystem->add(
            $this->filesystem->get(self::HITS)->add(
                new File(
                    $this->name($url->authority()->host()),
                    new StringStream(
                        $this
                            ->clock
                            ->now()
                            ->format(new ISO8601)
                    )
                )
            )
        );

        if ($this->isKnown($url)) {
            return $this;
        }

        $file = $this->filesystem->get(self::URLS);

        $this->filesystem->add(
            new File(
                self::URLS,
                new StringStream($file->content().$url."\n")
            )
        );

        return $this;
    }

    public function isKnown(UrlInterface $url): bool
    {
        $urls = new Str(
            (string) $this
                ->filesystem
                ->get(self::URLS)
                ->content()
        );

        try {
            $urls->position((string) $url);

            return true;
        } catch (SubstringException $e) {
            return false;
        }
    }

    public function lastHit(HostInterface $host): PointInTimeInterface
    {
        $name = $this->name($host);
        $directory = $this->filesystem->get(self::HITS);

        if (!$directory->has($name)) {
            throw new HostNeverHitException;
        }

        return $this->clock->at(
            (string) $directory
                ->get($name)
                ->content()
        );
    }

    private function name(HostInterface $host): string
    {
        return $host.'.txt';
    }
}
