<?php

declare(strict_types=1);

namespace Milon\Papyrus\Kdp;

use Milon\Papyrus\Config\DocumentSize;
use Milon\Papyrus\Config\KdpTrimBounds;
use Milon\Papyrus\Config\Project;

final class KdpMetadataExporter
{
    public function __construct(
        private readonly Project $project,
    ) {}

    public function export(?string $outputPath = null): string
    {
        $kdp = $this->project->kdpConfig();
        $slug = $this->project->outputSlug();
        $trim = $this->project->documentSize();
        $presetName = DocumentSize::resolvePresetName($trim->widthMm, $trim->heightMm);

        $path = $outputPath ?? sprintf(
            '%s/%s-kdp-metadata.json',
            $this->project->exportDir,
            $slug,
        );

        $data = [
            'title' => $this->project->title(),
            'subtitle' => $this->project->subtitle(),
            'author' => $this->project->author(),
            'language' => $kdp->metadataLanguage ?? $this->project->language(),
            'description' => $kdp->metadataDescription,
            'keywords' => $kdp->keywords,
            'ebook' => [
                'enabled' => $kdp->ebookEnabled,
                'cover' => $kdp->ebookCover,
                'artifact' => $slug.'-kdp.epub',
            ],
            'print' => [
                'enabled' => $kdp->printEnabled,
                'paper' => $kdp->printPaper,
                'bleed_mm' => $kdp->printBleedMm,
                'margin_preset' => $kdp->printMarginPreset,
                'margin_preset_known' => PrintMarginPreset::isKnown($kdp->printMarginPreset),
                'trim' => [
                    'width_mm' => $trim->widthMm,
                    'height_mm' => $trim->heightMm,
                    'width_in' => round($trim->widthMm / 25.4, 3),
                    'height_in' => round($trim->heightMm / 25.4, 3),
                    'preset' => $presetName,
                    'within_kdp_bounds' => KdpTrimBounds::isWithinBounds($trim),
                ],
                'artifact' => $slug.'-kdp-print.pdf',
            ],
            'artifacts' => [
                'ebook' => $slug.'-kdp.epub',
                'print' => $slug.'-kdp-print.pdf',
                'metadata' => $slug.'-kdp-metadata.json',
                'ebook_cover' => $kdp->ebookCover !== null
                    ? $slug.'-kdp-ebook-cover'.$this->extension($kdp->ebookCover)
                    : null,
                'print_covers' => array_values(array_filter(array_map(
                    function (string $theme) use ($slug): ?string {
                        $image = $this->project->coverImageForTheme($theme);

                        if ($image === null) {
                            return null;
                        }

                        return $slug.'-kdp-print-cover-'.$theme.$this->extension($image);
                    },
                    $this->project->themes(),
                ))),
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

    private function extension(string $filename): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        return $extension !== '' ? '.'.$extension : '';
    }
}
