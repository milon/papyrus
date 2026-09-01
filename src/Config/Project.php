<?php

declare(strict_types=1);

namespace Milon\Papyrus\Config;

use League\CommonMark\Environment\Environment;
use Milon\Papyrus\Book\Book;
use Milon\Papyrus\Markdown\BookConverter;
use Milon\Papyrus\Mermaid\MermaidCache;
use Milon\Papyrus\Mermaid\MermaidCliResolver;
use Milon\Papyrus\Mermaid\MermaidRenderer;

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

    public function bookConverter(?int $breakLevel = null): BookConverter
    {
        return new BookConverter(
            breakLevel: $breakLevel ?? $this->breakLevel(),
            configureCommonMark: $this->configureCommonMark(),
        );
    }

    public function mermaidConfig(): MermaidConfig
    {
        return MermaidConfig::fromConfig($this->config);
    }

    public function mermaidCacheDir(): string
    {
        return $this->dir.'/.papyrus/cache/mermaid';
    }

    public function mermaidRenderer(): MermaidRenderer
    {
        return new MermaidRenderer(
            project: $this,
            cli: MermaidCliResolver::resolve($this->mermaidConfig()->command),
            cache: new MermaidCache($this->mermaidCacheDir()),
            config: $this->mermaidConfig(),
        );
    }

    public function bookWithFigures(?int $breakLevel, string $exportTheme): Book
    {
        $book = $this->bookConverter($breakLevel)->convertDirectory($this->contentDir);

        if (! $this->mermaidConfig()->enabled) {
            return $book;
        }

        return $this->mermaidRenderer()->processBook($book, $exportTheme);
    }

    public function language(): string
    {
        $language = $this->config['language'] ?? 'en';

        return is_string($language) && $language !== '' ? $language : 'en';
    }

    public function documentSize(): DocumentSize
    {
        $document = $this->config['document'] ?? [];

        return DocumentSize::fromConfig(is_array($document) ? $document : []);
    }

    /**
     * @return array{H1: int, H2: int, H3: int}
     */
    public function tocLevels(): array
    {
        $toc = $this->config['toc'] ?? [];

        if (! is_array($toc)) {
            $toc = [];
        }

        return [
            'H1' => (int) ($toc['h1'] ?? 0),
            'H2' => (int) ($toc['h2'] ?? 0),
            'H3' => (int) ($toc['h3'] ?? 1),
        ];
    }

    public function headerStyle(): string
    {
        $header = $this->config['header'] ?? [];

        if (! is_array($header)) {
            return 'font-style: italic; text-align: right; border-bottom: solid 1px #808080;';
        }

        return (string) ($header['style'] ?? 'font-style: italic; text-align: right; border-bottom: solid 1px #808080;');
    }

    public function coverImageForTheme(string $theme): ?string
    {
        $cover = $this->config['cover'] ?? [];

        if (! is_array($cover)) {
            return null;
        }

        if (isset($cover[$theme]) && is_string($cover[$theme]) && $cover[$theme] !== '') {
            return $cover[$theme];
        }

        if (isset($cover['image']) && is_string($cover['image']) && $cover['image'] !== '') {
            return $cover['image'];
        }

        return null;
    }

    public function outputSlug(): string
    {
        $slug = strtolower(trim($this->title()));
        $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'book';
    }

    public function fontRegistry(): FontRegistry
    {
        return FontRegistry::fromProject($this);
    }

    public function sampleConfig(): SampleConfig
    {
        return SampleConfig::fromConfig($this->config);
    }

    public function kdpConfig(): KdpConfig
    {
        return KdpConfig::fromConfig($this->config);
    }

    private static function normalizeDir(string $dir): string
    {
        $resolved = realpath($dir);

        return $resolved !== false ? $resolved : rtrim($dir, '/');
    }
}
