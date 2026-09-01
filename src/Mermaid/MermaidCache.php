<?php

declare(strict_types=1);

namespace Milon\Papyrus\Mermaid;

final class MermaidCache
{
    public function __construct(
        private readonly string $cacheDir,
    ) {}

    public function ensureDirectory(): void
    {
        if (! is_dir($this->cacheDir) && ! mkdir($this->cacheDir, 0755, true) && ! is_dir($this->cacheDir)) {
            throw new MermaidException(sprintf('Unable to create Mermaid cache directory: %s', $this->cacheDir));
        }
    }

    public function path(string $key, string $extension): string
    {
        return $this->cacheDir.'/'.$key.'.'.$extension;
    }

    public function has(string $key, string $extension): bool
    {
        return is_file($this->path($key, $extension));
    }

    public function get(string $key, string $extension): string
    {
        return file_get_contents($this->path($key, $extension)) ?: '';
    }
}
