<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Render\Html\HtmlRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HtmlRendererTest extends TestCase
{
    private string $fixtureDir;

    protected function setUp(): void
    {
        $this->fixtureDir = dirname(__DIR__).'/fixtures/mini-book';
    }

    #[Test]
    public function mini_book_builds_html_with_all_chapters(): void
    {
        $project = Project::load($this->fixtureDir);
        $outputDir = sys_get_temp_dir().'/papyrus-html-'.uniqid('', true);
        mkdir($outputDir);

        $outputPath = $outputDir.'/mini-book.html';

        try {
            $renderer = new HtmlRenderer($project);
            $path = $renderer->render($outputPath);

            $this->assertSame($outputPath, $path);
            $this->assertFileExists($path);

            $html = file_get_contents($path);
            $this->assertIsString($html);
            $this->assertStringContainsString('<title>Mini Book</title>', $html);
            $this->assertStringContainsString('Papyrus fixture', $html);
            $this->assertStringContainsString('<strong>world</strong>', $html);
            $this->assertStringContainsString('বন্ধন', $html);
            $this->assertStringContainsString("class='notice'", $html);
        } finally {
            if (is_file($outputPath)) {
                unlink($outputPath);
            }
            rmdir($outputDir);
        }
    }
}
