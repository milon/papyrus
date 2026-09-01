<?php

declare(strict_types=1);

namespace Milon\Papyrus\Kdp;

use Milon\Papyrus\Config\Project;

final class KdpCoverExporter
{
    public function __construct(
        private readonly Project $project,
    ) {}

    /**
     * @return list<string>
     */
    public function export(?string $exportDir = null): array
    {
        $exportDir = $exportDir ?? $this->project->exportDir;

        if (! is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        $paths = [];
        $kdp = $this->project->kdpConfig();
        $slug = $this->project->outputSlug();

        if ($kdp->ebookCover !== null) {
            $exported = $this->copyAsset(
                sourceName: $kdp->ebookCover,
                destination: sprintf('%s/%s-kdp-ebook-cover%s', $exportDir, $slug, $this->extension($kdp->ebookCover)),
            );

            if ($exported !== null) {
                $paths[] = $exported;
            }
        }

        foreach ($this->project->themes() as $theme) {
            $imageName = $this->project->coverImageForTheme($theme);

            if ($imageName === null) {
                continue;
            }

            $exported = $this->copyAsset(
                sourceName: $imageName,
                destination: sprintf('%s/%s-kdp-print-cover-%s%s', $exportDir, $slug, $theme, $this->extension($imageName)),
            );

            if ($exported !== null) {
                $paths[] = $exported;
            }
        }

        return $paths;
    }

    private function copyAsset(string $sourceName, string $destination): ?string
    {
        $source = $this->project->assetsDir.'/'.$sourceName;

        if (! is_file($source)) {
            return null;
        }

        if (! copy($source, $destination)) {
            throw new KdpException(sprintf('Unable to copy cover asset: %s', $source));
        }

        return $destination;
    }

    private function extension(string $filename): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        return $extension !== '' ? '.'.$extension : '';
    }
}
