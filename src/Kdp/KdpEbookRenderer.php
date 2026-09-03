<?php

declare(strict_types=1);

namespace Milon\Papyrus\Kdp;

use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Kdp\Validation\EpubcheckRunner;
use Milon\Papyrus\Kdp\Validation\KdpEpubValidator;
use Milon\Papyrus\Render\Epub\EpubOptions;
use Milon\Papyrus\Render\Epub\EpubRenderer;

final class KdpEbookRenderer
{
    public function __construct(
        private readonly Project $project,
    ) {}

    public function render(?string $outputPath = null, bool $requireEpubcheck = false): KdpRenderResult
    {
        $kdp = $this->project->kdpConfig();

        $path = $outputPath ?? sprintf(
            '%s/%s-kdp.epub',
            $this->project->exportDir,
            $this->project->outputSlug(),
        );

        $cover = $kdp->ebookCover ?? $this->project->coverImageForTheme('light');
        $description = $kdp->metadataDescription !== ''
            ? $kdp->metadataDescription
            : sprintf('%s - %s', $this->project->title(), $this->project->author());

        $options = new EpubOptions(
            coverImageName: $cover,
            description: $description,
        );

        (new EpubRenderer($this->project))->render($path, $options);

        $warnings = [];

        $validator = new KdpEpubValidator;
        $result = $validator->validate($path, $this->project);

        if (! $result->ok) {
            throw new KdpException('KDP EPUB validation failed: '.$result->message());
        }

        foreach ($result->warnings as $warning) {
            $warnings[] = $warning;
        }

        $epubcheck = new EpubcheckRunner;

        if ($epubcheck->isAvailable()) {
            $check = $epubcheck->validate($path);

            if (! $check->ok) {
                throw new KdpException('epubcheck failed: '.$check->message());
            }

            foreach ($check->warnings as $warning) {
                $warnings[] = $warning;
            }
        } elseif ($requireEpubcheck) {
            throw new KdpException('epubcheck is required but was not found on PATH.');
        } else {
            $warnings[] = 'epubcheck not found; skipped external validation.';
        }

        return new KdpRenderResult($path, array_values(array_unique($warnings)));
    }
}
