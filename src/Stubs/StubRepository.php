<?php

declare(strict_types=1);

namespace Milon\Papyrus\Stubs;

final class StubRepository
{
    public function __construct(
        private readonly string $stubsDir,
    ) {}

    public static function default(): self
    {
        return new self(dirname(__DIR__, 2).'/stubs');
    }

    /**
     * @return list<string> paths relative to book root
     */
    public function bookFiles(): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->stubsDir, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($this->stubsDir) + 1);
            $files[] = str_replace('\\', '/', $relative);
        }

        sort($files);

        return $files;
    }

    public function read(string $relativePath): string
    {
        $path = $this->stubsDir.'/'.ltrim($relativePath, '/');

        if (! is_file($path)) {
            throw new \InvalidArgumentException(sprintf('Stub not found: %s', $relativePath));
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new \RuntimeException(sprintf('Could not read stub: %s', $relativePath));
        }

        return $contents;
    }
}
