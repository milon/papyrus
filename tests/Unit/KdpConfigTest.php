<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Config\DocumentSize;
use Milon\Papyrus\Config\KdpConfig;
use Milon\Papyrus\Kdp\PrintMarginPreset;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class KdpConfigTest extends TestCase
{
    #[Test]
    public function from_config_parses_kdp_section(): void
    {
        $config = KdpConfig::fromConfig([
            'kdp' => [
                'ebook' => [
                    'enabled' => true,
                    'cover' => 'cover-ebook.jpg',
                ],
                'print' => [
                    'enabled' => false,
                    'bleed_mm' => 3,
                    'margin_preset' => 'minimum',
                    'paper' => 'cream',
                ],
                'metadata' => [
                    'description' => 'About the book',
                    'keywords' => ['php', 'books'],
                    'language' => 'en-GB',
                ],
            ],
        ]);

        $this->assertTrue($config->ebookEnabled);
        $this->assertSame('cover-ebook.jpg', $config->ebookCover);
        $this->assertFalse($config->printEnabled);
        $this->assertSame(3.0, $config->printBleedMm);
        $this->assertSame('minimum', $config->printMarginPreset);
        $this->assertSame('cream', $config->printPaper);
        $this->assertSame('About the book', $config->metadataDescription);
        $this->assertSame(['php', 'books'], $config->keywords);
        $this->assertSame('en-GB', $config->metadataLanguage);
        $this->assertTrue($config->hasEnabledOutputs());
    }

    #[Test]
    public function print_margin_preset_adds_bleed_to_trim_and_margins(): void
    {
        $trim = new DocumentSize(
            widthMm: 188.976,
            heightMm: 246.126,
            marginLeft: 27,
            marginRight: 27,
            marginTop: 14,
            marginBottom: 14,
        );

        $print = PrintMarginPreset::documentWithBleed($trim, 'recommended', 3);

        $this->assertSame(194.976, $print->widthMm);
        $this->assertSame(252.126, $print->heightMm);
        $this->assertSame(15.7, $print->marginLeft);
        $this->assertSame(12.525, $print->marginRight);
        $this->assertSame(15.7, $print->marginTop);
        $this->assertSame(15.7, $print->marginBottom);
    }
}
