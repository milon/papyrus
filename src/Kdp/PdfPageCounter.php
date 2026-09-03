<?php

declare(strict_types=1);

namespace Milon\Papyrus\Kdp;

final class PdfPageCounter
{
    public static function count(string $pdfPath): ?int
    {
        if (! is_file($pdfPath)) {
            return null;
        }

        $fromPdfInfo = self::fromPdfInfo($pdfPath);

        if ($fromPdfInfo !== null) {
            return $fromPdfInfo;
        }

        return self::fromPdfContents($pdfPath);
    }

    private static function fromPdfInfo(string $pdfPath): ?int
    {
        $command = trim((string) shell_exec('command -v pdfinfo 2>/dev/null'));

        if ($command === '') {
            return null;
        }

        $output = [];
        $exitCode = 1;
        exec($command.' '.escapeshellarg($pdfPath).' 2>/dev/null', $output, $exitCode);

        if ($exitCode !== 0) {
            return null;
        }

        foreach ($output as $line) {
            if (preg_match('/^Pages:\s+(\d+)\s*$/', $line, $matches) === 1) {
                return (int) $matches[1];
            }
        }

        return null;
    }

    private static function fromPdfContents(string $pdfPath): ?int
    {
        $contents = @file_get_contents($pdfPath);

        if ($contents === false || $contents === '') {
            return null;
        }

        if (preg_match_all('/\/Type\s*\/Page(?!s)\b/', $contents, $matches) > 0) {
            $count = count($matches[0]);

            return $count > 0 ? $count : null;
        }

        return null;
    }
}
