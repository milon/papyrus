<?php

declare(strict_types=1);

namespace Milon\Papyrus\Kdp;

use Milon\Papyrus\Config\Project;
use ZipArchive;

final class KdpPackageBuilder
{
    public function __construct(
        private readonly Project $project,
    ) {}

    public function build(?string $outputPath = null): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new KdpException('ext-zip is required for kdp:package.');
        }

        $kdp = $this->project->kdpConfig();

        if (! $kdp->hasEnabledOutputs()) {
            throw new KdpException('No KDP outputs are enabled in papyrus.php.');
        }

        $slug = $this->project->outputSlug();
        $exportDir = $this->project->exportDir;
        $path = $outputPath ?? sprintf('%s/%s-kdp-package.zip', $exportDir, $slug);

        $files = $this->collectFiles($exportDir, $slug);

        if ($files === []) {
            throw new KdpException(
                'No KDP artifacts found to package. Run papyrus kdp (or kdp:ebook / kdp:print / kdp:cover / kdp:metadata) first.',
            );
        }

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        if (is_file($path)) {
            unlink($path);
        }

        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE) !== true) {
            throw new KdpException(sprintf('Unable to create package zip: %s', $path));
        }

        try {
            foreach ($files as $absolute => $relative) {
                if (! $zip->addFile($absolute, $relative)) {
                    throw new KdpException(sprintf('Unable to add %s to package.', $relative));
                }
            }

            $checklist = $this->checklist($files);
            $zip->addFromString('KDP-CHECKLIST.txt', $checklist);
        } finally {
            $zip->close();
        }

        return $path;
    }

    /**
     * @return array<string, string> absolute => archive relative name
     */
    private function collectFiles(string $exportDir, string $slug): array
    {
        $kdp = $this->project->kdpConfig();
        $candidates = [];

        if ($kdp->ebookEnabled) {
            $candidates[] = $slug.'-kdp.epub';
        }

        if ($kdp->printEnabled) {
            $candidates[] = $slug.'-kdp-print.pdf';
        }

        $candidates[] = $slug.'-kdp-metadata.json';

        if ($kdp->ebookCover !== null) {
            $ext = pathinfo($kdp->ebookCover, PATHINFO_EXTENSION);
            $candidates[] = $slug.'-kdp-ebook-cover'.($ext !== '' ? '.'.$ext : '');
        }

        foreach ($this->project->themes() as $theme) {
            $image = $this->project->coverImageForTheme($theme);

            if ($image === null) {
                continue;
            }

            $ext = pathinfo($image, PATHINFO_EXTENSION);
            $candidates[] = $slug.'-kdp-print-cover-'.$theme.($ext !== '' ? '.'.$ext : '');
        }

        $files = [];

        foreach (array_unique($candidates) as $name) {
            $absolute = $exportDir.'/'.$name;

            if (is_file($absolute)) {
                $files[$absolute] = $name;
            }
        }

        return $files;
    }

    /**
     * @param  array<string, string>  $files
     */
    private function checklist(array $files): string
    {
        $kdp = $this->project->kdpConfig();
        $lines = [
            'Papyrus KDP upload checklist',
            'Book: '.$this->project->title(),
            'Author: '.$this->project->author(),
            '',
            'Included files:',
        ];

        foreach ($files as $relative) {
            $lines[] = '  - '.$relative;
        }

        $lines[] = '';
        $lines[] = 'Reminders:';
        $lines[] = '  - Upload the Kindle EPUB ('.$this->project->outputSlug().'-kdp.epub) for the eBook.';
        $lines[] = '  - Upload the print interior PDF for paperback/hardcover interiors.';
        $lines[] = '  - Build a full wraparound cover PDF yourself (Papyrus copies front covers only).';
        $lines[] = '  - Use Amazon’s cover calculator with spine estimates from kdp:metadata / kdp:cover --dimensions.';
        $lines[] = '  - Confirm keywords, description, and categories in the KDP dashboard.';

        if ($kdp->ebookEnabled && $kdp->metadataDescription === '') {
            $lines[] = '  - Warning: kdp.metadata.description is empty.';
        }

        $lines[] = '';

        return implode("\n", $lines);
    }
}
