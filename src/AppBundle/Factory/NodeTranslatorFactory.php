<?php
declare(strict_types = 1);

namespace AppBundle\Factory;

use Innmind\Html\Translator\NodeTranslators as HtmlTranslators;
use Innmind\Xml\{
    Translator\NodeTranslator,
    Translator\NodeTranslators
};

final class NodeTranslatorFactory
{
    public static function make(): NodeTranslator
    {
        return new NodeTranslator(
            NodeTranslators::defaults()->merge(
                HtmlTranslators::defaults()
            )
        );
    }
}
