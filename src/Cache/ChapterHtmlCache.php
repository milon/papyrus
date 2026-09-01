<?php

declare(strict_types=1);

namespace Milon\Papyrus\Cache;

use RuntimeException;

final class ChapterHtmlCache
{
    public function __construct(
        private readonly string $cacheDir,
    ) {}

    public function ensureDirectory(): void
    {
        if (! is_dir($this->cacheDir) && ! mkdir($this->cacheDir, 0755, true) && ! is_dir($this->cacheDir)) {
            throw new RuntimeException(sprintf('Unable to create cache directory: %s', $this->cacheDir));
        }
    }

    public function get(string $relativePath, string $contentHash, string $configHash, int $breakLevel): ?CachedChapter
    {
        $path = $this->entryPath($relativePath);

        if (! is_file($path)) {
            return null;
        }

        $payload = json_decode((string) file_get_contents($path), true);

        if (! is_array($payload)) {
            return null;
        }

        if (($payload['content_hash'] ?? '') !== $contentHash) {
            return null;
        }

        if (($payload['config_hash'] ?? '') !== $configHash) {
            return null;
        }

        if ((int) ($payload['break_level'] ?? -1) !== $breakLevel) {
            return null;
        }

        return new CachedChapter(
            rawHtml: (string) ($payload['html'] ?? ''),
            frontMatter: is_array($payload['front_matter'] ?? null) ? $payload['front_matter'] : [],
            pretoc: (bool) ($payload['pretoc'] ?? false),
        );
    }

    public function put(
        string $relativePath,
        string $contentHash,
        string $configHash,
        int $breakLevel,
        CachedChapter $chapter,
    ): void {
        $this->ensureDirectory();

        $payload = json_encode([
            'content_hash' => $contentHash,
            'config_hash' => $configHash,
            'break_level' => $breakLevel,
            'html' => $chapter->rawHtml,
            'front_matter' => $chapter->frontMatter,
            'pretoc' => $chapter->pretoc,
        ], JSON_UNESCAPED_UNICODE);

        if ($payload === false) {
            throw new RuntimeException(sprintf('Unable to encode cache entry for %s', $relativePath));
        }

        if (file_put_contents($this->entryPath($relativePath), $payload) === false) {
            throw new RuntimeException(sprintf('Unable to write cache entry for %s', $relativePath));
        }
    }

    public static function contentHash(string $markdown): string
    {
        return hash('sha256', $markdown);
    }

    private function entryPath(string $relativePath): string
    {
        return $this->cacheDir.'/'.hash('sha256', $relativePath).'.json';
    }
}
