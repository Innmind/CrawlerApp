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
        $definition = $container->getDefinition('parser');
        $ids = $container->findTaggedServiceIds('parser');
        $priorities = [];

        foreach ($ids as $id => $tags) {
            foreach ($tags as $tag => $attributes) {
                $priority = 0;

                if (isset($attributes['priority'])) {
                    $priority = $attributes['priority'];
                }

                if (!isset($priorities[$priority])) {
                    $priorities[$priority] = [];
                }

                $priorities[$priority][] = $id;
            }
        }

        krsort($priorities);

        foreach ($priorities as $priority => $parsers) {
            foreach ($parsers as $parser) {
                $definition->addMethodCall('addPass', [new Reference($parser)]);
            }
        }
    }
}
