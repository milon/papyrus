<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Theme\Theme;
use Milon\Papyrus\Theme\ThemeException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ThemeTest extends TestCase
{
    #[Test]
    public function it_splits_on_papyrus_toc_marker_and_interpolates_placeholders(): void
    {
        $path = sys_get_temp_dir().'/papyrus-theme-'.uniqid('', true).'.html';
        file_put_contents($path, <<<'HTML'
<h1>{{$title}}</h1>
<!-- PAPYRUS:TOC -->
<tocpagebreak links="on">
HTML);

        try {
            $theme = Theme::load($path, [
                '{{$title}}' => 'My Book',
                '{{$subtitle}}' => '',
                '{{$author}}' => '',
            ]);

            $this->assertStringContainsString('My Book', $theme->head);
            $this->assertStringNotContainsString('{{$title}}', $theme->head);
            $this->assertStringContainsString('<tocpagebreak', $theme->tail);
            $this->assertStringContainsString('<tocpagebreak', $theme->preamble());
        } finally {
            unlink($path);
        }
    }

    #[Test]
    public function it_injects_default_toc_when_tail_is_empty(): void
    {
        $path = sys_get_temp_dir().'/papyrus-theme-'.uniqid('', true).'.html';
        file_put_contents($path, "<div>Title</div>\n<!-- PAPYRUS:TOC -->\n");

        try {
            $theme = Theme::load($path, []);

            $this->assertStringContainsString('<tocpagebreak', $theme->tail);
        } finally {
            unlink($path);
        }
    }

    #[Test]
    public function missing_marker_throws(): void
    {
        $path = sys_get_temp_dir().'/papyrus-theme-'.uniqid('', true).'.html';
        file_put_contents($path, '<div>No marker</div>');

        try {
            $this->expectException(ThemeException::class);
            Theme::load($path, []);
        } finally {
            unlink($path);
        }
    }
}
