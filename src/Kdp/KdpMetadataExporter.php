<?php

declare(strict_types=1);

namespace Milon\Papyrus\Kdp;

use Milon\Papyrus\Config\Project;

final class KdpMetadataExporter
{
    public function __construct(
        private readonly Project $project,
    ) {}

    public function export(?string $outputPath = null): string
    {
        $kdp = $this->project->kdpConfig();

        $path = $outputPath ?? sprintf(
            '%s/%s-kdp-metadata.json',
            $this->project->exportDir,
            $this->project->outputSlug(),
        );

        $data = [
            'title' => $this->project->title(),
            'subtitle' => $this->project->subtitle(),
            'author' => $this->project->author(),
            'language' => $kdp->metadataLanguage ?? $this->project->language(),
            'description' => $kdp->metadataDescription,
            'keywords' => $kdp->keywords,
            'print' => [
                'paper' => $kdp->printPaper,
                'bleed_mm' => $kdp->printBleedMm,
                'margin_preset' => $kdp->printMarginPreset,
            ],
        ];

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($encoded === false) {
            throw new KdpException('Unable to encode KDP metadata JSON.');
        }

        if (file_put_contents($path, $encoded."\n") === false) {
            throw new KdpException(sprintf('Unable to write KDP metadata: %s', $path));
        }

        return $path;
    }
}
