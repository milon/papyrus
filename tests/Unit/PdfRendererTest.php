<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Render\Pdf\PdfRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PdfRendererTest extends TestCase
{
    private string $fixtureDir;

    protected function setUp(): void
    {
        $this->fixtureDir = dirname(__DIR__).'/fixtures/mini-book';
    }

    #[Test]
    public function mini_book_builds_light_pdf_with_crown_quarto_page_size(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('ext-gd is required for mPDF');
        }

        $project = Project::load($this->fixtureDir);
        $outputDir = sys_get_temp_dir().'/papyrus-pdf-'.uniqid('', true);
        mkdir($outputDir);

        $outputPath = $outputDir.'/mini-book-light.pdf';

        try {
            $renderer = new PdfRenderer($project);
            $path = $renderer->render('light', $outputPath);

            $this->assertSame($outputPath, $path);
            $this->assertFileExists($path);

            $header = file_get_contents($path, false, null, 0, 5);
            $this->assertSame('%PDF-', $header);

            $this->assertMediaBoxMatchesCrownQuarto($path);
        } finally {
            if (is_file($outputPath)) {
                unlink($outputPath);
            }
            rmdir($outputDir);
        }
    }

    private function assertMediaBoxMatchesCrownQuarto(string $pdfPath): void
    {
        $pdfinfo = trim((string) shell_exec('command -v pdfinfo'));

        if ($pdfinfo === '') {
            return;
        }

        $output = shell_exec(sprintf('pdfinfo %s 2>/dev/null', escapeshellarg($pdfPath)));

        if (! is_string($output) || ! preg_match('/Page size:\s+([\d.]+)\s+x\s+([\d.]+)\s+pts/m', $output, $matches)) {
            return;
        }

        $widthPt = (float) $matches[1];
        $heightPt = (float) $matches[2];

        $expectedWidth = 188.976 * 72 / 25.4;
        $expectedHeight = 246.126 * 72 / 25.4;

        $this->assertEqualsWithDelta($expectedWidth, $widthPt, 1.0);
        $this->assertEqualsWithDelta($expectedHeight, $heightPt, 1.0);
    }
}
