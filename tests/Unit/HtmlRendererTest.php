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
            $this->assertStringContainsString('<p>Papyrus fixture</p>', $html);
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

    #[Test]
    public function html_build_can_fall_back_to_bundled_assets(): void
    {
        $bookDir = sys_get_temp_dir().'/papyrus-html-bundled-'.uniqid('', true);
        $outputDir = $bookDir.'/export';

        mkdir($bookDir.'/content', 0755, true);
        mkdir($bookDir.'/assets', 0755, true);
        mkdir($outputDir, 0755, true);

        file_put_contents($bookDir.'/content/01.md', "---\ntitle: One\n---\n\nHello from bundled assets.\n");
        file_put_contents($bookDir.'/papyrus.php', <<<'PHP'
<?php

return [
    'title' => 'Bundled Asset Book',
    'author' => 'Papyrus',
    'themes' => ['light'],
    'mermaid' => ['enabled' => false],
];
PHP);

        try {
            $project = Project::load($bookDir)->withExportDir($outputDir);
            $path = (new HtmlRenderer($project))->render();

            $this->assertFileExists($path);

            $html = file_get_contents($path);
            $this->assertIsString($html);
            $this->assertStringContainsString('<title>Bundled Asset Book</title>', $html);
            $this->assertStringContainsString('Hello from bundled assets.', $html);
        } finally {
            $this->removeDir($bookDir);
        }
    }

    private function removeDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($dir);
    }
}
