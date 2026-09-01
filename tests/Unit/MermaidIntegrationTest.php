<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Mermaid\MermaidCliResolver;
use Milon\Papyrus\Render\Epub\EpubRenderer;
use Milon\Papyrus\Render\Html\HtmlRenderer;
use Milon\Papyrus\Render\Pdf\PdfRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class MermaidIntegrationTest extends TestCase
{
    #[Test]
    public function mini_book_renders_flowchart_in_pdf_html_and_epub(): void
    {
        if (! MermaidCliResolver::resolve(null)->isAvailable()) {
            $this->markTestSkipped('mmdc (@mermaid-js/mermaid-cli) is not available');
        }

        if (! $this->canRenderWithMermaid()) {
            $this->markTestSkipped('mmdc is available but could not render (Chrome/puppeteer may be missing)');
        }

        $projectDir = $this->prepareMermaidProject();
        $project = Project::load($projectDir);
        $cacheDir = $project->mermaidCacheDir();
        $this->clearDirectory($cacheDir);

        try {
            $htmlPath = $projectDir.'/export/mini-book.html';
            $epubPath = $projectDir.'/export/mini-book.epub';
            $pdfPath = $projectDir.'/export/mini-book-light.pdf';

            (new HtmlRenderer($project))->render($htmlPath);
            (new EpubRenderer($project))->render($epubPath);

            if (extension_loaded('gd')) {
                (new PdfRenderer($project))->render('light', $pdfPath);
                $this->assertSame('%PDF-', file_get_contents($pdfPath, false, null, 0, 5));
            }

            $html = file_get_contents($htmlPath);
            $this->assertIsString($html);
            $this->assertStringContainsString('<figure class="mermaid"', $html);

            if (class_exists(ZipArchive::class)) {
                $zip = new ZipArchive;
                $this->assertTrue($zip->open($epubPath));
                $archiveText = $this->readArchiveText($zip);
                $zip->close();
                $this->assertStringContainsString('<figure class="mermaid"', $archiveText);
            }

            $cachedFiles = glob($cacheDir.'/*.svg') ?: [];
            $this->assertNotEmpty($cachedFiles);

            (new HtmlRenderer($project))->render($projectDir.'/export/mini-book-2.html');
            $cachedFilesAfter = glob($cacheDir.'/*.svg') ?: [];
            $this->assertCount(count($cachedFiles), $cachedFilesAfter);
        } finally {
            $this->removeDirectory($projectDir);
        }
    }

    private function prepareMermaidProject(): string
    {
        $source = dirname(__DIR__).'/fixtures/mini-book';
        $target = sys_get_temp_dir().'/papyrus-mermaid-book-'.uniqid('', true);
        $this->copyDirectory($source, $target);

        $configPath = $target.'/papyrus.php';
        $config = file_get_contents($configPath);
        $this->assertIsString($config);
        $config = str_replace("'enabled' => false,", "'enabled' => true,", $config);
        file_put_contents($configPath, $config);

        return $target;
    }

    private function canRenderWithMermaid(): bool
    {
        $dir = sys_get_temp_dir().'/papyrus-mermaid-probe-'.uniqid('', true);
        mkdir($dir);

        $input = $dir.'/probe.mmd';
        $output = $dir.'/probe.svg';
        file_put_contents($input, "flowchart TD\n  A --> B\n");

        try {
            MermaidCliResolver::resolve(null)->render($input, $output, 'default');

            return is_file($output);
        } catch (\Throwable) {
            return false;
        } finally {
            if (is_file($input)) {
                unlink($input);
            }
            if (is_file($output)) {
                unlink($output);
            }
            rmdir($dir);
        }
    }

    private function copyDirectory(string $source, string $target): void
    {
        mkdir($target);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $file) {
            $destination = $target.DIRECTORY_SEPARATOR.substr($file->getPathname(), strlen($source) + 1);

            if ($file->isDir()) {
                mkdir($destination);
            } else {
                copy($file->getPathname(), $destination);
            }
        }
    }

    private function readArchiveText(ZipArchive $zip): string
    {
        $text = '';

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);

            if (! is_string($name) || (! str_ends_with(strtolower($name), '.html') && ! str_ends_with(strtolower($name), '.xhtml'))) {
                continue;
            }

            $chunk = $zip->getFromIndex($index);
            $text .= is_string($chunk) ? $chunk : '';
        }

        return $text;
    }

    private function clearDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (glob($dir.'/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    private function removeDirectory(string $dir): void
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
