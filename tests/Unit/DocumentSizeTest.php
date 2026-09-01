<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Config\ConfigException;
use Milon\Papyrus\Config\DocumentSize;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DocumentSizeTest extends TestCase
{
    #[Test]
    public function crown_quarto_preset_matches_kdp_trim_size(): void
    {
        $size = DocumentSize::fromConfig(['size' => 'crown-quarto']);

        $this->assertSame(188.976, $size->widthMm);
        $this->assertSame(246.126, $size->heightMm);
    }

    #[Test]
    public function format_array_overrides_preset(): void
    {
        $size = DocumentSize::fromConfig([
            'size' => 'crown-quarto',
            'format' => [100, 150],
            'margin_left' => 10,
        ]);

        $this->assertSame(100.0, $size->widthMm);
        $this->assertSame(150.0, $size->heightMm);
        $this->assertSame(10.0, $size->marginLeft);
    }

    #[Test]
    public function unknown_preset_throws(): void
    {
        $this->expectException(ConfigException::class);

        DocumentSize::fromConfig(['size' => 'unknown-size']);
    }
}
