<?php

declare(strict_types=1);

namespace Milon\Papyrus\Markdown\Extensions;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;

final class AsideExtension implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment
            ->addBlockStartParser(new AsideParser)
            ->addRenderer(Aside::class, new AsideRenderer);
    }
}
