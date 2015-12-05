<?php

namespace CrawlerBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class RegisterParserPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $parser = $container->getDefinition('parser');
        $parsers = $container->findTaggedServiceIds('parser');

        foreach ($parsers as $id => $tags) {
            $parser->addMethodCall('addPass', [new Reference($id)]);
        }
    }
}
