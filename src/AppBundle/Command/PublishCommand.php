<?php
declare(strict_types = 1);

namespace AppBundle\Command;

use Innmind\Url\Url;
use Symfony\Component\Console\{
    Input\InputArgument,
    Input\InputInterface,
    Output\OutputInterface
};
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

final class PublishCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('publish')
            ->addArgument('url', InputArgument::REQUIRED)
            ->addArgument('server', InputArgument::REQUIRED, 'The server where to publish');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this
            ->getContainer()
            ->get('publisher')(
                Url::fromString($input->getArgument('url')),
                Url::fromString($input->getArgument('server'))
            );
    }
}
