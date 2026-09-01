<?php

declare(strict_types=1);

namespace Milon\Papyrus\Migration;

final class ThemeMigrator
{
    private const IBIS_MARKER = '<!-- IBIS:TOC -->';

    private const PAPYRUS_MARKER = '<!-- PAPYRUS:TOC -->';

    /**
     * @return list<string> Updated theme file paths
     */
    public function migrateDirectory(string $assetsDir): array
    {
        if (! is_dir($assetsDir)) {
            return [];
        }

        $updated = [];

        foreach (glob($assetsDir.'/theme*.html') ?: [] as $path) {
            if (! is_string($path)) {
                continue;
            }

            if ($this->migrateFile($path)) {
                $updated[] = $path;
            }
        }

        return $updated;
    }

    public function migrateFile(string $path): bool
    {
        if (! is_file($path)) {
            return false;
        }

        $contents = file_get_contents($path);

        if ($contents === false || ! str_contains($contents, self::IBIS_MARKER)) {
            return false;
        }

        $updated = str_replace(self::IBIS_MARKER, self::PAPYRUS_MARKER, $contents);

        if ($updated === $contents) {
            return false;
        }

        if (file_put_contents($path, $updated) === false) {
            throw new MigrationException(sprintf('Unable to update theme file: %s', $path));
        }

        return true;
    }
}
