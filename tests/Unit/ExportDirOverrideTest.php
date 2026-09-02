<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Render\Html\HtmlRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ExportDirOverrideTest extends TestCase
{
    #[Test]
    public function with_export_dir_overrides_default_export_path(): void
    {
        $fixture = dirname(__DIR__).'/fixtures/mini-book';
        $export = sys_get_temp_dir().'/papyrus-export-override-'.uniqid('', true);
        $project = Project::load($fixture)->withExportDir($export);

        $this->assertSame(Project::normalizePath($export), $project->exportDir);
        $this->assertSame($fixture, $project->dir);
    }

    #[Test]
    public function relative_path_climbs_out_of_export_into_assets_fonts(): void
    {
        $from = '/book/docs';
        $to = '/book/examples/handbook/assets/fonts';

        $this->assertSame(
            '../examples/handbook/assets/fonts',
            Project::relativePath($from, $to),
        );
    }

    #[Test]
    public function html_build_rewrites_font_urls_for_custom_export_dir(): void
    {
        $book = dirname(__DIR__, 2).'/examples/the-papyrus-handbook';
        $export = sys_get_temp_dir().'/papyrus-html-export-'.uniqid('', true);
        mkdir($export);

        $project = Project::load($book)->withExportDir($export);
        $htmlPath = $export.'/the-papyrus-handbook.html';

        try {
            $path = (new HtmlRenderer($project))->render($htmlPath);
            $html = file_get_contents($path);
            $this->assertIsString($html);

            $expectedPrefix = Project::relativePath($export, $project->assetsDir.'/fonts').'/';
            $this->assertStringContainsString('url("'.$expectedPrefix, $html);
            $this->assertStringNotContainsString('../assets/fonts/', $html);
        } finally {
            if (is_file($htmlPath)) {
                unlink($htmlPath);
            }
            if (is_dir($export)) {
                rmdir($export);
            }
        }
    }
}
