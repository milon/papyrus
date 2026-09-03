<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Render\Pdf\PdfRenderer;
use Milon\Papyrus\Render\Pdf\SamplePdfRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SamplePdfRendererTest extends TestCase
{
    #[Test]
    public function sample_pdf_matches_full_book_page_size(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('ext-gd is required for PDF sample tests');
        }

        $fixtureDir = dirname(__DIR__).'/fixtures/mini-book';
        $project = Project::load($fixtureDir);
        $outputDir = sys_get_temp_dir().'/papyrus-sample-'.uniqid('', true);
        mkdir($outputDir);

        $fullPath = $outputDir.'/mini-book-light.pdf';
        $samplePath = $outputDir.'/sample-mini-book-light.pdf';

        try {
            (new PdfRenderer($project))->render('light', $fullPath);
            (new SamplePdfRenderer($project))->render('light', $samplePath);

            $this->assertFileExists($samplePath);
            $this->assertSame('%PDF-', file_get_contents($samplePath, false, null, 0, 5));

            $fullSize = $this->pageSizePoints($fullPath);
            $sampleSize = $this->pageSizePoints($samplePath);

            if ($fullSize !== null && $sampleSize !== null) {
                $this->assertEqualsWithDelta($fullSize['width'], $sampleSize['width'], 1.0);
                $this->assertEqualsWithDelta($fullSize['height'], $sampleSize['height'], 1.0);
            }
        } finally {
            if (is_file($fullPath)) {
                unlink($fullPath);
            }
            if (is_file($samplePath)) {
                unlink($samplePath);
            }
            rmdir($outputDir);
        }
    }

    #[Test]
    public function sample_pdf_can_include_chapters_by_filename(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('ext-gd is required for PDF sample tests');
        }

        $bookDir = sys_get_temp_dir().'/papyrus-sample-chapters-'.uniqid('', true);
        $exportDir = $bookDir.'/export';
        $this->copyMiniBook($bookDir);

        file_put_contents($bookDir.'/papyrus.php', <<<'PHP'
<?php

return [
    'title' => 'Chapter Sample Book',
    'author' => 'Papyrus',
    'themes' => ['light'],
    'document' => [
        'size' => 'crown-quarto',
        'margin_left' => 27,
        'margin_right' => 27,
        'margin_top' => 14,
        'margin_bottom' => 14,
    ],
    'cover' => [
        'image' => 'cover.png',
    ],
    'mermaid' => ['enabled' => false],
    'sample' => [
        'chapters' => [
            '01-chapter-one.md',
        ],
    ],
    'sample_notice' => 'Chapter sample notice',
];
PHP);

        try {
            $project = Project::load($bookDir)->withExportDir($exportDir);
            $path = (new SamplePdfRenderer($project))->render('light');

            $this->assertFileExists($path);
            $this->assertSame('%PDF-', file_get_contents($path, false, null, 0, 5));

            $text = (string) shell_exec(sprintf('pdftotext %s - 2>/dev/null', escapeshellarg($path)));
            $this->assertStringContainsString('Chapter sample notice', $text);
            $this->assertStringNotContainsString('Table of Contents', $text);
        } finally {
            $this->removeDir($bookDir);
        }
    }

    private function copyMiniBook(string $destination): void
    {
        $source = dirname(__DIR__).'/fixtures/mini-book';
        mkdir($destination.'/content', 0755, true);
        mkdir($destination.'/assets', 0755, true);

        foreach (['00-copyright.md', '01-chapter-one.md'] as $file) {
            copy($source.'/content/'.$file, $destination.'/content/'.$file);
        }

        foreach (scandir($source.'/assets') ?: [] as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $from = $source.'/assets/'.$file;

            if (is_file($from)) {
                copy($from, $destination.'/assets/'.$file);
            }
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

    /**
     * @return array{width: float, height: float}|null
     */
    private function pageSizePoints(string $pdfPath): ?array
    {
        $pdfinfo = trim((string) shell_exec('command -v pdfinfo'));

        if ($pdfinfo === '') {
            return null;
        }

        $output = shell_exec(sprintf('pdfinfo %s 2>/dev/null', escapeshellarg($pdfPath)));

        if (! is_string($output) || preg_match('/Page size:\s+([\d.]+)\s+x\s+([\d.]+)\s+pts/m', $output, $matches) !== 1) {
            return null;
        }

        return [
            'width' => (float) $matches[1],
            'height' => (float) $matches[2],
        ];
    }
}
