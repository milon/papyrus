<?php

declare(strict_types=1);

namespace Milon\Papyrus\Kdp\Validation;

final class EpubcheckRunner
{
    public function isAvailable(): bool
    {
        return $this->resolveCommand() !== null;
    }

    public function validate(string $epubPath): ValidationResult
    {
        $command = $this->resolveCommand();

        if ($command === null) {
            return new ValidationResult(true, warnings: ['epubcheck not found; skipped external validation.']);
        }

        $fullCommand = $command.' '.escapeshellarg($epubPath).' 2>&1';
        $outputLines = [];
        $exitCode = 1;
        exec($fullCommand, $outputLines, $exitCode);
        $output = implode("\n", $outputLines);

        if ($exitCode === 0) {
            return new ValidationResult(true);
        }

        return new ValidationResult(false, [trim($output) !== '' ? trim($output) : 'epubcheck failed.']);
    }

    private function resolveCommand(): ?string
    {
        $path = trim((string) shell_exec('command -v epubcheck 2>/dev/null'));

        return $path !== '' ? 'epubcheck' : null;
    }
}
