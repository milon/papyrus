<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Config\MermaidConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MermaidConfigTest extends TestCase
{
    #[Test]
    public function omitted_theme_defaults_to_auto_book_palette(): void
    {
        $config = MermaidConfig::fromConfig([
            'mermaid' => ['enabled' => true],
        ]);

        $this->assertSame('auto', $config->theme);
        $this->assertTrue($config->usesBookPalette());
        $this->assertTrue($config->isDualExport('html'));
    }

    #[Test]
    public function blank_theme_defaults_to_auto(): void
    {
        $config = MermaidConfig::fromConfig([
            'mermaid' => ['theme' => ''],
        ]);

        $this->assertSame('auto', $config->theme);
        $this->assertTrue($config->usesBookPalette());
    }

    #[Test]
    public function auto_theme_follows_export_theme(): void
    {
        $config = MermaidConfig::fromConfig([
            'mermaid' => ['theme' => 'auto'],
        ]);

        $this->assertTrue($config->usesBookPalette());
        $this->assertSame('dark', $config->resolvedTheme('dark'));
        $this->assertSame('light', $config->resolvedTheme('light'));
        $this->assertSame('light', $config->bookVariant('default'));
        $this->assertTrue($config->isDualExport('html'));
        $this->assertFalse($config->isDualExport('light'));
    }

    #[Test]
    public function fixed_theme_skips_book_palette(): void
    {
        $config = MermaidConfig::fromConfig([
            'mermaid' => ['theme' => 'forest'],
        ]);

        $this->assertFalse($config->usesBookPalette());
        $this->assertFalse($config->isDualExport('html'));
        $this->assertSame('forest', $config->resolvedTheme('dark'));
    }
}
