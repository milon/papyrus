<?php

declare(strict_types=1);

namespace Milon\Papyrus\Mermaid;

interface MermaidCli
{
    public function command(): string;

    public function isAvailable(): bool;

    public function version(): ?string;

    public function render(string $inputPath, string $outputPath, string $theme): void;
}
