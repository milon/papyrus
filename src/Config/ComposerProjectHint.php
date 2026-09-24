<?php

declare(strict_types=1);

namespace Milon\Papyrus\Config;

/**
 * Optional hints from a nearby composer.json when scaffolding docs.
 */
final class ComposerProjectHint
{
    public function __construct(
        public readonly string $packageName,
        public readonly string $description,
        public readonly ?string $homepage,
        public readonly ?string $sourceUrl,
    ) {}

    public static function discover(string $scaffoldDir): ?self
    {
        foreach ([$scaffoldDir.'/composer.json', dirname($scaffoldDir).'/composer.json'] as $path) {
            if (is_file($path)) {
                return self::fromFile($path);
            }
        }

        return null;
    }

    public static function fromFile(string $path): ?self
    {
        $raw = file_get_contents($path);

        if ($raw === false) {
            return null;
        }

        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (! is_array($data)) {
            return null;
        }

        $name = is_string($data['name'] ?? null) ? $data['name'] : '';
        $description = is_string($data['description'] ?? null) ? $data['description'] : '';
        $homepage = is_string($data['homepage'] ?? null) && $data['homepage'] !== ''
            ? $data['homepage']
            : null;

        $source = null;
        $support = $data['support'] ?? null;

        if (is_array($support) && is_string($support['source'] ?? null) && $support['source'] !== '') {
            $source = $support['source'];
        }

        if ($source === null && str_contains($name, '/')) {
            $source = 'https://github.com/'.$name;
        }

        if ($name === '' && $description === '') {
            return null;
        }

        return new self(
            packageName: $name !== '' ? $name : 'vendor/package',
            description: $description,
            homepage: $homepage,
            sourceUrl: $source,
        );
    }

    public function displayTitle(): string
    {
        $segment = str_contains($this->packageName, '/')
            ? substr($this->packageName, (int) strrpos($this->packageName, '/') + 1)
            : $this->packageName;

        $segment = str_replace(['-', '_'], ' ', $segment);

        return $segment !== '' ? ucwords($segment) : 'My Package';
    }

    public function suggestedBasePath(): string
    {
        $segment = str_contains($this->packageName, '/')
            ? substr($this->packageName, (int) strrpos($this->packageName, '/') + 1)
            : $this->packageName;

        $segment = trim($segment, '/');

        return $segment !== '' ? '/'.$segment : '/your-repo';
    }

    public function packagistUrl(): string
    {
        return 'https://packagist.org/packages/'.$this->packageName;
    }
}
