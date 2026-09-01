<?php

declare(strict_types=1);

namespace Milon\Papyrus\Config;

use League\CommonMark\Environment\Environment;
use Milon\Papyrus\Markdown\BookConverter;

final class Project
{
    public const CONFIG_FILE = 'papyrus.php';

    public const DEFAULT_CONTENT_DIR = 'content';

    public const DEFAULT_ASSETS_DIR = 'assets';

    public const DEFAULT_EXPORT_DIR = 'export';

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        public readonly string $dir,
        public readonly string $configPath,
        public readonly array $config,
        public readonly string $contentDir,
        public readonly string $assetsDir,
        public readonly string $exportDir,
    ) {}

    public static function load(string $dir): self
    {
        $dir = self::normalizeDir($dir);
        $configPath = $dir.'/'.self::CONFIG_FILE;

        if (! is_file($configPath)) {
            throw new ConfigException(sprintf('Missing %s in %s', self::CONFIG_FILE, $dir));
        }

        $config = require $configPath;

        if (! is_array($config)) {
            throw new ConfigException(sprintf('%s must return an array', self::CONFIG_FILE));
        }

        return new self(
            dir: $dir,
            configPath: $configPath,
            config: $config,
            contentDir: $dir.'/'.(string) ($config['content_dir'] ?? self::DEFAULT_CONTENT_DIR),
            assetsDir: $dir.'/'.(string) ($config['assets_dir'] ?? self::DEFAULT_ASSETS_DIR),
            exportDir: $dir.'/'.(string) ($config['export_dir'] ?? self::DEFAULT_EXPORT_DIR),
        );
    }

    public function title(): string
    {
        return (string) ($this->config['title'] ?? 'Untitled');
    }

    public function subtitle(): string
    {
        return (string) ($this->config['subtitle'] ?? '');
    }

    public function author(): string
    {
        return (string) ($this->config['author'] ?? '');
    }

    /**
     * @return list<string>
     */
    public function themes(): array
    {
        $themes = $this->config['themes'] ?? ['light', 'dark'];

        if (! is_array($themes)) {
            return ['light', 'dark'];
        }

        return array_values(array_filter(array_map('strval', $themes)));
    }

    public function breakLevel(): int
    {
        $level = $this->config['break_level'] ?? 2;

        return is_int($level) ? $level : (int) $level;
    }

    /**
     * @return (callable(Environment): void)|null
     */
    public function configureCommonMark(): ?callable
    {
        $hook = $this->config['configure_commonmark'] ?? null;

        return is_callable($hook) ? $hook : null;
    }

    public function bookConverter(): BookConverter
    {
        return new BookConverter(
            breakLevel: $this->breakLevel(),
            configureCommonMark: $this->configureCommonMark(),
        );
    }

    private static function normalizeDir(string $dir): string
    {
        $resolved = realpath($dir);

        return $resolved !== false ? $resolved : rtrim($dir, '/');
    }
}
