<?php

declare(strict_types=1);

namespace Milon\Papyrus\Kdp\Validation;

use Milon\Papyrus\Config\Project;
use ZipArchive;

final class KdpEpubValidator
{
    private const MIN_COVER_EDGE_PX = 1600;

    public function validate(string $epubPath, Project $project): ValidationResult
    {
        $errors = [];
        $warnings = [];

        if (! is_file($epubPath)) {
            return new ValidationResult(false, ['EPUB file not found.']);
        }

        if ($project->title() === '' || $project->title() === 'Untitled') {
            $errors[] = 'Book title is required for KDP.';
        }

        if ($project->author() === '') {
            $warnings[] = 'Author is empty; KDP may require an author name.';
        }

        $kdp = $project->kdpConfig();

        if ($kdp->metadataDescription === '') {
            $warnings[] = 'kdp.metadata.description is empty; KDP store listing will need a blurb.';
        }

        $coverName = $kdp->ebookCover ?? $project->coverImageForTheme('light');

        if ($coverName === null) {
            $warnings[] = 'No eBook cover configured (kdp.ebook.cover or cover.image).';
        } else {
            $coverPath = $project->assetsDir.'/'.$coverName;

            if (! is_file($coverPath)) {
                $warnings[] = sprintf('Configured KDP eBook cover not found: %s', $coverName);
            } else {
                foreach ($this->coverDimensionWarnings($coverPath, $coverName) as $warning) {
                    $warnings[] = $warning;
                }
            }
        }

        if (! class_exists(ZipArchive::class)) {
            return new ValidationResult($errors === [], $errors, ['ext-zip unavailable; skipped EPUB structure checks.']);
        }

        $zip = new ZipArchive;

        if ($zip->open($epubPath) !== true) {
            return new ValidationResult(false, [...$errors, 'EPUB is not a valid ZIP archive.']);
        }

        try {
            $mimetype = $zip->getFromName('mimetype');

            if ($mimetype !== 'application/epub+zip') {
                $errors[] = 'Missing or invalid mimetype file.';
            }

            if ($zip->getFromName('META-INF/container.xml') === false) {
                $errors[] = 'Missing META-INF/container.xml.';
            }

            $hasContent = false;

            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = $zip->getNameIndex($index);

                if (is_string($name) && preg_match('/\.x?html$/i', $name) === 1) {
                    $hasContent = true;

                    break;
                }
            }

            if (! $hasContent) {
                $errors[] = 'EPUB contains no HTML content files.';
            }
        } finally {
            $zip->close();
        }

        return new ValidationResult($errors === [], $errors, $warnings);
    }

    /**
     * @return list<string>
     */
    private function coverDimensionWarnings(string $coverPath, string $coverName): array
    {
        if (! function_exists('getimagesize')) {
            return [];
        }

        $size = @getimagesize($coverPath);

        if ($size === false) {
            return [sprintf('Unable to read eBook cover dimensions: %s', $coverName)];
        }

        [$width, $height] = $size;
        $warnings = [];

        if ($width < self::MIN_COVER_EDGE_PX || $height < self::MIN_COVER_EDGE_PX) {
            $warnings[] = sprintf(
                'eBook cover %s is %dx%d px; Amazon recommends at least %d px on the shortest side.',
                $coverName,
                $width,
                $height,
                self::MIN_COVER_EDGE_PX,
            );
        }

        return $warnings;
    }
}
