<?php

declare(strict_types=1);

namespace Milon\Papyrus\Kdp;

use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Render\Pdf\PdfRenderer;

final class KdpPrintRenderer
{
    public function __construct(
        private readonly Project $project,
    ) {}

    public function render(string $themeName, ?string $outputPath = null): string
    {
        $kdp = $this->project->kdpConfig();

        $document = PrintMarginPreset::documentWithBleed(
            $this->project->documentSize(),
            $kdp->printMarginPreset,
            $kdp->printBleedMm,
        );

        $path = $outputPath ?? sprintf(
            '%s/%s-kdp-print.pdf',
            $this->project->exportDir,
            $this->project->outputSlug(),
        );

        return (new PdfRenderer($this->project))->render(
            themeName: $themeName,
            outputPath: $path,
            documentSize: $document,
            skipCover: true,
        );
    }
}
