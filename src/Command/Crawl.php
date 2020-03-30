<?php
declare(strict_types = 1);

namespace Crawler\Command;

use Crawler\Publisher;
use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
};
use Innmind\Crawler\Crawler;
use Innmind\Http\{
    Message\Request\Request,
    Message\Method\Method,
    ProtocolVersion\ProtocolVersion,
    Headers\Headers,
    Header,
    Header\Value\Value,
};
use Innmind\Url\Url;
use Innmind\Crawler\HttpResource\{
    Attribute,
    Attributes,
};
use Innmind\Stream\Writable;
use Innmind\Immutable\{
    Set,
    MapInterface,
    SetInterface,
    Str,
};

final class Crawl implements Command
{
    private Crawler $crawl;
    private string $userAgent;
    private Publisher $publish;

    public function __construct(
        Crawler $crawl,
        string $userAgent,
        Publisher $publish
    ) {
        $this->crawl = $crawl;
        $this->userAgent = $userAgent;
        $this->publish = $publish;
    }

    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
    {
        $resource = ($this->crawl)(
            new Request(
                Url::fromString($arguments->get('url')),
                new Method(Method::GET),
                new ProtocolVersion(2, 0),
                Headers::of(
                    new Header\Header(
                        'User-Agent',
                        new Value($this->userAgent)
                    )
                )
            )
        );

        $output = $env->output();
        $output->write(Str::of('Resource attributes:'."\n"));
        $resource
            ->attributes()
            ->foreach(function(string $name, Attribute $attribute) use ($output): void {
                $this->print($output, $name, $attribute);
            });

        if ($arguments->contains('publish')) {
            ($this->publish)(
                $resource,
                Url::fromString($arguments->get('publish'))
            );
        }
    }

    public function __toString(): string
    {
        return <<<USAGE
crawl url [publish]

Crawl the given url and will print all the attributes found

The "publish" argument is an optional url where to publish the crawled resource
USAGE;
    }

    private function print(
        Writable $output,
        string $name,
        Attribute $attribute
    ): void {
        switch (true) {
            case $attribute instanceof Attributes:
                $output->write(Str::of("$name:\n"));
                $attribute
                    ->content()
                    ->foreach(function($key, Attribute $value) use ($output): void {
                        $this->print($output, $key, $value);
                    });
                break;

            case $attribute->content() instanceof MapInterface:
                $output->write(
                    Str::of('%s: map<%s, %s>[%s]')
                        ->sprintf(
                            $name,
                            $attribute->content()->keyType(),
                            $attribute->content()->valueType(),
                            $attribute->content()->size()
                        )
                        ->append("\n")
                );
                break;

            case $attribute->content() instanceof SetInterface:
                $output->write(
                    Str::of('%s: set<%s>[%s]')
                        ->sprintf(
                            $name,
                            $attribute->content()->type(),
                            $attribute->content()->size()
                        )
                        ->append("\n")
                );
                break;

            default:
                $output->write(
                    Str::of('%s: string[%s]')
                        ->sprintf(
                            $name,
                            (string) Str::of((string) $attribute->content())->length()
                        )
                        ->append("\n")
                );
        }
    }
}
