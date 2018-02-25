<?php
declare(strict_types = 1);

namespace AppBundle\Command;

use AppBundle\Publisher;
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
use Innmind\Immutable\{
    Map,
    Set,
    MapInterface,
    SetInterface,
    Str,
};

final class Crawl implements Command
{
    private $crawler;
    private $userAgent;
    private $publish;

    public function __construct(
        Crawler $crawler,
        string $userAgent,
        Publisher $publish
    ) {
        $this->crawler = $crawler;
        $this->userAgent = $userAgent;
        $this->publish = $publish;
    }

    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
    {
        $resource = $this->crawler->execute(
            new Request(
                Url::fromString($arguments->get('url')),
                new Method(Method::GET),
                new ProtocolVersion(2, 0),
                new Headers(
                    (new Map('string', Header::class))
                        ->put(
                            'User-Agent',
                            new Header\Header(
                                'User-Agent',
                                new Value($this->userAgent)
                            )
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

        if ($arguments->contains('publish-to')) {
            ($this->publish)(
                $resource,
                Url::fromString($arguments->get('publish-to'))
            );
        }
    }

    public function __toString(): string
    {
        return 'crawl url [publish-to]';
    }

    private function print(
        Writable $output,
        string $name,
        Attribute $attribute,
        int $level = 0
    ): void {
        switch (true) {
            case $attribute instanceof Attributes:
                $output->write(Str::of("$name:\n"));
                $attribute
                    ->content()
                    ->foreach(function($key, Attribute $value) use ($output, $level): void {
                        $this->print($output, $key, $value, $level + 1);
                    });
                break;

            case $attribute->content() instanceof MapInterface:
                $output->write(Str::of("$name:\n"));
                $attribute
                    ->content()
                    ->foreach(function($key, $value) use ($output, $level): void {
                        $output->write(Str::of(str_repeat('    ', $level + 1)."$key: $value\n"));
                    });
                break;

            case $attribute->content() instanceof SetInterface:
                $output->write(Str::of("$name:\n"));
                $attribute
                    ->content()
                    ->foreach(
                        function($value) use ($output, $level): void {
                            $output->write(Str::of(str_repeat('    ', $level + 1)."$value\n"));
                        }
                    );
                break;

            default:
                $output->write(Str::of(str_repeat('    ', $level)."$name: {$attribute->content()}\n"));
        }
    }
}
