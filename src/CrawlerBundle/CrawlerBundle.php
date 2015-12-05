<?php

namespace CrawlerBundle;

use CrawlerBundle\DependencyInjection\Compiler\RegisterParserPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CrawlerBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterParserPass);
    }
}
