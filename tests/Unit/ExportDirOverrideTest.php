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
        $bookDir = $this->prepareFontBook();
        $export = sys_get_temp_dir().'/papyrus-html-export-'.uniqid('', true);
        mkdir($export);

        $project = Project::load($bookDir)->withExportDir($export);
        $htmlPath = $export.'/font-book.html';

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
            $this->removeDirectory($bookDir);
        }
    }

    private function prepareFontBook(): string
    {
        $dir = sys_get_temp_dir().'/papyrus-font-book-'.uniqid('', true);
        mkdir($dir.'/content', 0755, true);
        mkdir($dir.'/assets/fonts', 0755, true);

        file_put_contents($dir.'/content/01.md', "---\ntitle: One\n---\n\nHello.\n");
        file_put_contents($dir.'/assets/fonts/Dummy.ttf', 'font');
        file_put_contents($dir.'/papyrus.php', <<<'PHP'
<?php

return [
    'title' => 'Font Book',
    'author' => 'Papyrus',
    'themes' => ['light'],
    'mermaid' => ['enabled' => false],
];
PHP);
        file_put_contents($dir.'/assets/theme-html.html', <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{$title}}</title>
    <style>
        @font-face {
            font-family: "Dummy";
            src: url("../assets/fonts/Dummy.ttf") format("truetype");
        }
        body { font-family: "Dummy", serif; }
    </style>
</head>
<body>
    <h1>{{$title}}</h1>
    {{$body}}
</body>
</html>
HTML);

        return $dir;
    }

    private function removeDirectory(string $dir): void
    {
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
