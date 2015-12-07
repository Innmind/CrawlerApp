<?php

namespace CrawlerBundle\Command;

use Innmind\Crawler\Request;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class CrawlCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('crawl')
            ->addArgument('url', InputArgument::REQUIRED);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $url = $input->getArgument('url');

        $crawler = $this
            ->getContainer()
            ->get('crawler');
        $resource = $crawler->crawl(new Request($url));
        $output->writeln(sprintf(
            '<info>URL crawled:</> <fg=yellow>%s</>',
            $resource->getUrl()
        ));
        $output->writeln(sprintf(
            '<info>Resource content type</>: <fg=yellow>%s</>',
            $resource->getContentType()
        ));

        foreach ($resource->keys() as $key) {
            $output->writeln(sprintf(
                '<info>%s</>: <fg=yellow>%s</>',
                $key,
                var_export($resource->get($key), true)
            ));
        }

        $stopwatch = $crawler->getStopwatch($resource);
        $crawler->release($resource);

        $output->writeln('');
        $output->writeln('<info>Crawl times:</>');

        foreach ($stopwatch->getSections() as $sectionName => $section) {
            foreach ($section->getEvents() as $eventName => $event) {
                $output->writeln(sprintf(
                    '<info>%s.%s</>: <fg=yellow>%s ms</> (<fg=cyan>%s Mo</>)',
                    $sectionName,
                    $eventName,
                    $event->getDuration(),
                    $event->getMemory() / 1024**2
                ));
            }
        }
    }
}
