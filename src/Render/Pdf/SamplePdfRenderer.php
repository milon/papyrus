<?php

declare(strict_types=1);

namespace Milon\Papyrus\Render\Pdf;

use Milon\Papyrus\Config\Project;
use Mpdf\MpdfException;

final class SamplePdfRenderer
{
    public function __construct(
        private readonly Project $project,
    ) {}

    public function render(string $themeName, ?string $outputPath = null): string
    {
        $sample = $this->project->sampleConfig();

        if (! $sample->hasRanges()) {
            throw new SampleException('No sample page ranges configured in papyrus.php (sample.ranges).');
        }

        $fullPath = tempnam(sys_get_temp_dir(), 'papyrus-full-');

        if ($fullPath === false) {
            throw new SampleException('Unable to create temporary PDF file.');
        }

        $fullPdfPath = $fullPath.'.pdf';
        rename($fullPath, $fullPdfPath);

        try {
            (new PdfRenderer($this->project))->render($themeName, $fullPdfPath);

            $document = $this->project->documentSize();
            $pdf = MpdfFactory::create($this->project, $document);

            $pageCount = $pdf->SetSourceFile($fullPdfPath);

            if ($pageCount < 1) {
                throw new SampleException('Full book PDF has no pages.');
            }

            $imported = false;

            foreach ($sample->ranges as $range) {
                $from = max(1, $range['from']);
                $to = min($range['to'], $pageCount);

                for ($page = $from; $page <= $to; $page++) {
                    $pdf->AddPage();
                    $templateId = $pdf->ImportPage($page);
                    $pdf->UseTemplate($templateId);
                    $imported = true;
                }
            }

            if (! $imported) {
                throw new SampleException('Sample page ranges did not match any pages in the full PDF.');
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
            if (is_file($fullPdfPath)) {
                unlink($fullPdfPath);
            }
        }
    }
}
