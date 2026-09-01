<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Config\MermaidConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MermaidConfigTest extends TestCase
{
    #[Test]
    public function auto_theme_follows_export_theme(): void
    {
        $config = MermaidConfig::fromConfig([
            'mermaid' => ['theme' => 'auto'],
        ]);

        $this->assertSame('dark', $config->resolvedTheme('dark'));
        $this->assertSame('default', $config->resolvedTheme('light'));
    }
}
