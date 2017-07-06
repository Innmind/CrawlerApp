<?php
declare(strict_types = 1);

namespace AppBundle\Command;

use Innmind\Http\{
    Message\Request,
    Message\Method,
    ProtocolVersion,
    Headers,
    Header\HeaderInterface,
    Header\HeaderValueInterface,
    Header\Header,
    Header\HeaderValue
};
use Innmind\Url\Url;
use Innmind\Crawler\HttpResource\{
    AttributeInterface,
    AttributesInterface
};
use Innmind\Immutable\{
    Map,
    Set,
    MapInterface,
    SetInterface
};
use Symfony\Component\Console\{
    Input\InputArgument,
    Input\InputOption,
    Input\InputInterface,
    Output\OutputInterface
};
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

final class CrawlCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('crawl')
            ->addArgument('url', InputArgument::REQUIRED)
            ->addOption('publish-to', null, InputOption::VALUE_REQUIRED, '', false);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $resource = $this
            ->getContainer()
            ->get('crawler')
            ->execute(
                new Request(
                    Url::fromString($input->getArgument('url')),
                    new Method(Method::GET),
                    new ProtocolVersion(2, 0),
                    new Headers(
                        (new Map('string', HeaderInterface::class))
                            ->put(
                                'User-Agent',
                                new Header(
                                    'User-Agent',
                                    (new Set(HeaderValueInterface::class))
                                        ->add(new HeaderValue(
                                            $this
                                                ->getContainer()
                                                ->getParameter('user_agent')
                                        ))
                                )
                            )
                    )
                )
            );

        $output->writeln('<info>Resource attributes:</>');
        $resource
            ->attributes()
            ->foreach(function(string $name, AttributeInterface $attribute) use ($output): void {
                $this->print($output, $name, $attribute);
            });

        if ($input->getOption('publish-to')) {
            $this
                ->getContainer()
                ->get('publisher')(
                    $resource,
                    Url::fromString($input->getOption('publish-to'))
                );
        }
    }

    private function print(
        OutputInterface $output,
        string $name,
        AttributeInterface $attribute,
        int $level = 0
    ): void {
        switch (true) {
            case $attribute instanceof AttributesInterface:
                $output->writeln(sprintf('<fg=yellow>%s</>:', $name));
                $attribute
                    ->content()
                    ->foreach(function($key, AttributeInterface $value) use ($output, $level): void {
                        $this->print($output, $key, $value, $level + 1);
                    });
                break;

            case $attribute->content() instanceof MapInterface:
                $output->writeln(sprintf('<fg=yellow>%s</>:', $name));
                $attribute
                    ->content()
                    ->foreach(function($key, $value) use ($output, $level): void {
                        $output->writeln(str_repeat('    ', $level + 1).sprintf(
                            '<fg=yellow>%s</>: <fg=cyan>%s</>',
                            $key,
                            $value
                        ));
                    });
                break;

            case $attribute->content() instanceof SetInterface:
                $output->writeln(sprintf('<fg=yellow>%s</>:', $name));
                $attribute
                    ->content()
                    ->foreach(
                        function($value) use ($output, $level): void {
                            $output->writeln(str_repeat('    ', $level + 1).sprintf(
                                '<fg=cyan>%s</>', $value
                            ));
                        }
                    );
                break;

            default:
                $output->writeln(str_repeat('    ', $level).sprintf(
                    '<fg=yellow>%s</>: <fg=cyan>%s</>',
                    $name,
                    $attribute->content()
                ));
        }
    }
}
