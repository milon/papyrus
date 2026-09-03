<?php

declare(strict_types=1);

namespace Milon\Papyrus\Render\Pdf;

use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Render\VendorNotices;
use Mpdf\Mpdf;
use Mpdf\MpdfException;

final class SamplePdfRenderer
{
    public function __construct(
        private readonly Project $project,
    ) {}

    public function render(string $themeName, ?string $outputPath = null): string
    {
        return VendorNotices::silence(
            fn (): string => $this->write($themeName, $outputPath),
        );
    }

    private function write(string $themeName, ?string $outputPath): string
    {
        $sample = $this->project->sampleConfig();

        if (! $sample->hasSelection()) {
            throw new SampleException(
                'No sample selection configured in papyrus.php (sample.ranges and/or sample.chapters).',
            );
        }

        $temps = [];

        try {
            $document = $this->project->documentSize();
            $pdf = MpdfFactory::create($this->project, $document);
            $imported = false;

            if ($sample->hasRanges()) {
                $fullPdf = $this->tempPdfPath('papyrus-sample-full-');
                $temps[] = $fullPdf;
                (new PdfRenderer($this->project))->render($themeName, $fullPdf);
                $imported = $this->importPageRanges($pdf, $fullPdf, $sample->ranges) || $imported;
            }

            if ($sample->hasChapters()) {
                $chapterPdf = $this->tempPdfPath('papyrus-sample-chapters-');
                $temps[] = $chapterPdf;
                $this->renderChapterSelection($themeName, $sample->chapters, $chapterPdf);
                $imported = $this->importAllPages($pdf, $chapterPdf) || $imported;
            }

            if (! $imported) {
                throw new SampleException('Sample selection did not produce any PDF pages.');
            }

            if ($sample->notice !== '') {
                $pdf->AddPage();
                $pdf->WriteHTML(sprintf(
                    '<p style="text-align: center; font-size: 16px; line-height: 40px;">%s</p>',
                    $sample->notice,
                ));
            }

            if (! is_dir($this->project->exportDir)) {
                mkdir($this->project->exportDir, 0755, true);
            }

            $filename = $outputPath ?? sprintf(
                '%s/sample-%s-%s.pdf',
                $this->project->exportDir,
                $this->project->outputSlug(),
                $themeName,
            );

            $pdf->Output($filename);

            return $filename;
        } catch (MpdfException $e) {
            throw new SampleException($e->getMessage(), previous: $e);
        } finally {
            foreach ($temps as $temp) {
                if (is_file($temp)) {
                    unlink($temp);
                }
            }
        }
    }

    /**
     * @param  list<string>  $chapterNames
     */
    private function renderChapterSelection(string $themeName, array $chapterNames, string $outputPath): void
    {
        $book = $this->project->bookWithFigures(breakLevel: null, exportTheme: $themeName);
        $missing = $book->missingChapterNames($chapterNames);

        if ($missing !== []) {
            throw new SampleException(sprintf(
                'Unknown sample chapter(s): %s',
                implode(', ', $missing),
            ));
        }

        $selected = $book->selectInOrder($chapterNames);

        if ($selected->chapters === []) {
            throw new SampleException('sample.chapters did not match any content files.');
        }

        (new PdfRenderer($this->project))->render(
            themeName: $themeName,
            outputPath: $outputPath,
            skipCover: true,
            book: $selected,
            bodyOnly: true,
        );
    }

    private function importAllPages(Mpdf $pdf, string $sourcePdf): bool
    {
        $pageCount = $pdf->SetSourceFile($sourcePdf);

        if ($pageCount < 1) {
            return false;
        }

        for ($page = 1; $page <= $pageCount; $page++) {
            $pdf->AddPage();
            $templateId = $pdf->ImportPage($page);
            $pdf->UseTemplate($templateId);
        }

        return true;
    }

    /**
     * @param  list<array{from: int, to: int}>  $ranges
     */
    private function importPageRanges(Mpdf $pdf, string $sourcePdf, array $ranges): bool
    {
        $pageCount = $pdf->SetSourceFile($sourcePdf);

        if ($pageCount < 1) {
            throw new SampleException('Full book PDF has no pages.');
        }

        $imported = false;

        foreach ($ranges as $range) {
            $from = max(1, $range['from']);
            $to = min($range['to'], $pageCount);

            for ($page = $from; $page <= $to; $page++) {
                $pdf->AddPage();
                $templateId = $pdf->ImportPage($page);
                $pdf->UseTemplate($templateId);
                $imported = true;
            }
        }

        return $imported;
    }

    private function tempPdfPath(string $prefix): string
    {
        $fullPath = tempnam(sys_get_temp_dir(), $prefix);

        if ($fullPath === false) {
            throw new SampleException('Unable to create temporary PDF file.');
        }

        $pdfPath = $fullPath.'.pdf';
        rename($fullPath, $pdfPath);

        return $pdfPath;
    }
}
