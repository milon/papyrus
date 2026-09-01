<?php

declare(strict_types=1);

namespace Milon\Papyrus\Watch;

final class ProjectWatcher
{
    /**
     * @return list<string>
     */
    public function watchedPaths(string $projectDir, string $contentDir, string $assetsDir, string $configPath): array
    {
        $paths = [$configPath];

        foreach ([$contentDir, $assetsDir] as $dir) {
            if (! is_dir($dir)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $paths[] = $file->getPathname();
                }
            }
        }

        return $paths;
    }

    /**
     * @param  list<string>  $paths
     * @return array<string, int>
     */
    public function snapshot(array $paths): array
    {
        $snapshot = [];

        foreach ($paths as $path) {
            if (is_file($path)) {
                $snapshot[$path] = filemtime($path) ?: 0;
            }
        }

        return $snapshot;
    }

    /**
     * @param  array<string, int>  $previous
     * @param  array<string, int>  $current
     * @return list<string>
     */
    public function changedFiles(array $previous, array $current): array
    {
        $changed = [];

        foreach ($current as $path => $mtime) {
            if (! isset($previous[$path]) || $previous[$path] !== $mtime) {
                $changed[] = $path;
            }
        }

        foreach (array_keys($previous) as $path) {
            if (! isset($current[$path])) {
                $changed[] = $path;
            }
        }

        return $changed;
    }
}
