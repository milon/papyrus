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
