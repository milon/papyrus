<?php

declare(strict_types=1);

namespace Milon\Papyrus\Kdp;

use Milon\Papyrus\Config\Project;

final class KdpBuilder
{
    public function __construct(
        private readonly Project $project,
    ) {}

    public function buildEbook(bool $requireEpubcheck = false): ?KdpRenderResult
    {
        if (! $this->project->kdpConfig()->ebookEnabled) {
            return null;
        }

        return (new KdpEbookRenderer($this->project))->render(requireEpubcheck: $requireEpubcheck);
    }

    public function buildPrint(string $themeName): ?string
    {
        if (! $this->project->kdpConfig()->printEnabled) {
            return null;
        }

        return (new KdpPrintRenderer($this->project))->render($themeName);
    }

    /**
     * @return list<string>
     */
    public function buildCovers(): array
    {
        return (new KdpCoverExporter($this->project))->export();
    }

    /**
     * @return list<string>
     */
    public function buildWrapCover(int $pageCount, string $theme = 'light'): array
    {
        return (new WrapCoverRenderer($this->project))->render($pageCount, $theme);
    }

    public function buildMetadata(): string
    {
        return (new KdpMetadataExporter($this->project))->export();
    }

    public function buildPackage(?string $outputPath = null): string
    {
        return (new KdpPackageBuilder($this->project))->build($outputPath);
    }

    public function hasEnabledOutputs(): bool
    {
        return $this->project->kdpConfig()->hasEnabledOutputs();
    }
}
