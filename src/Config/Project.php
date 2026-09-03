<?php

declare(strict_types=1);

namespace Milon\Papyrus\Config;

use League\CommonMark\Environment\Environment;
use Milon\Papyrus\Book\Book;
use Milon\Papyrus\Cache\ChapterHtmlCache;
use Milon\Papyrus\Markdown\BookConverter;
use Milon\Papyrus\Mermaid\MermaidCache;
use Milon\Papyrus\Mermaid\MermaidCliResolver;
use Milon\Papyrus\Mermaid\MermaidRenderer;

final class Project
{
    public const CONFIG_FILE = 'papyrus.php';

    public const DEFAULT_CONTENT_DIR = 'content';

    public const DEFAULT_ASSETS_DIR = 'assets';

    public const PACKAGE_ASSETS_DIR = __DIR__.'/../../stubs/assets';

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
        public readonly bool $includeDrafts = false,
    ) {}

    public function withExportDir(string $exportDir): self
    {
        return new self(
            dir: $this->dir,
            configPath: $this->configPath,
            config: $this->config,
            contentDir: $this->contentDir,
            assetsDir: $this->assetsDir,
            exportDir: self::normalizePath($exportDir),
            includeDrafts: $this->includeDrafts,
        );
    }

    public function withIncludeDrafts(bool $includeDrafts = true): self
    {
        return new self(
            dir: $this->dir,
            configPath: $this->configPath,
            config: $this->config,
            contentDir: $this->contentDir,
            assetsDir: $this->assetsDir,
            exportDir: $this->exportDir,
            includeDrafts: $includeDrafts,
        );
    }

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

    public function bookConverter(?int $breakLevel = null, bool $useCache = true): BookConverter
    {
        $level = $breakLevel ?? $this->breakLevel();
        $cache = $useCache ? new ChapterHtmlCache($this->markdownCacheDir()) : null;

        return new BookConverter(
            breakLevel: $level,
            configureCommonMark: $this->configureCommonMark(),
            cache: $cache,
            configHash: $this->configHash(),
        );
    }

    public function markdownCacheDir(): string
    {
        return $this->dir.'/.papyrus/cache/markdown';
    }

    public function configHash(): string
    {
        $contents = file_get_contents($this->configPath);

        return hash('sha256', $contents !== false ? $contents : '');
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

        if (! $this->includeDrafts) {
            $book = $book->withoutDrafts();
        }

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

    public function packageAssetsDir(): string
    {
        return self::normalizePath(self::PACKAGE_ASSETS_DIR);
    }

    public function assetPath(string $relativePath): ?string
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');

        if ($relativePath === '') {
            return null;
        }

        $projectPath = $this->assetsDir.'/'.$relativePath;

        if (is_file($projectPath) || is_dir($projectPath)) {
            return $projectPath;
        }

        $packagePath = $this->packageAssetsDir().'/'.$relativePath;

        if (is_file($packagePath) || is_dir($packagePath)) {
            return $packagePath;
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function fontDirs(): array
    {
        $dirs = [];

        if (is_dir($this->assetsDir.'/fonts')) {
            $dirs[] = $this->assetsDir.'/fonts';
        }

        $packageFontsDir = $this->packageAssetsDir().'/fonts';

        if (is_dir($packageFontsDir)) {
            $dirs[] = $packageFontsDir;
        }

        return array_values(array_unique($dirs));
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

    /**
     * Optional site home banner filename under assets/ (e.g. banner.jpg).
     * Falls back to assets/banner.jpg when present.
     */
    public function siteBanner(): ?string
    {
        $site = $this->config['site'] ?? [];

        if (is_array($site) && isset($site['banner']) && is_string($site['banner']) && $site['banner'] !== '') {
            return $site['banner'];
        }

        if (is_file($this->assetsDir.'/banner.jpg')) {
            return 'banner.jpg';
        }

        if (is_file($this->assetsDir.'/banner.png')) {
            return 'banner.png';
        }

        return null;
    }

    public function siteLead(): ?string
    {
        $site = $this->config['site'] ?? [];

        if (! is_array($site)) {
            return null;
        }

        $lead = $site['lead'] ?? null;

        return is_string($lead) && $lead !== '' ? $lead : null;
    }

    /**
     * Optional custom domain for GitHub Pages (`CNAME` file in the site root).
     * Accepts a bare host or a URL; stores the hostname only.
     */
    public function siteCname(): ?string
    {
        $site = $this->config['site'] ?? [];

        if (! is_array($site)) {
            return null;
        }

        $cname = $site['cname'] ?? null;

        if (! is_string($cname)) {
            return null;
        }

        $host = trim($cname);

        if ($host === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $host) === 1) {
            $parsed = parse_url($host);
            $host = is_array($parsed) && isset($parsed['host']) && is_string($parsed['host'])
                ? $parsed['host']
                : $host;
        }

        $host = strtolower(rtrim(explode('/', $host, 2)[0], '.'));

        return $host !== '' ? $host : null;
    }

    /**
     * Optional URL path prefix for project GitHub Pages (e.g. /my-repo).
     * Empty string means the site is hosted at the domain root.
     */
    public function siteBasePath(): string
    {
        $site = $this->config['site'] ?? [];

        if (! is_array($site)) {
            return '';
        }

        $basePath = $site['base_path'] ?? null;

        if (! is_string($basePath)) {
            return '';
        }

        $basePath = trim(str_replace('\\', '/', $basePath));

        if ($basePath === '' || $basePath === '/') {
            return '';
        }

        return '/'.trim($basePath, '/');
    }

    /**
     * @return list<array{label: string, url?: string, chapter?: string}>
     */
    public function siteLinks(): array
    {
        $site = $this->config['site'] ?? [];

        if (! is_array($site)) {
            return [];
        }

        $links = [];
        $rawLinks = $site['links'] ?? null;

        if (is_array($rawLinks)) {
            foreach ($rawLinks as $link) {
                if (! is_array($link)) {
                    continue;
                }

                $label = $link['label'] ?? null;

                if (! is_string($label) || trim($label) === '') {
                    continue;
                }

                $item = ['label' => trim($label)];

                $url = $link['url'] ?? null;

                if (is_string($url) && trim($url) !== '') {
                    $item['url'] = trim($url);
                }

                $chapter = $link['chapter'] ?? null;

                if (is_string($chapter) && trim($chapter) !== '') {
                    $item['chapter'] = trim($chapter);
                }

                if (! isset($item['url']) && ! isset($item['chapter'])) {
                    continue;
                }

                $links[] = $item;
            }

            return $links;
        }

        $repository = $site['repository'] ?? null;

        if (! is_string($repository) || $repository === '') {
            return [];
        }

        $links[] = [
            'label' => 'Source on GitHub',
            'url' => $repository,
        ];

        if (preg_match('#github\.com/([^/]+/[^/]+?)(?:\.git)?/?$#', $repository, $matches) === 1) {
            $slug = $matches[1];
            $links[] = [
                'label' => 'Packagist',
                'url' => 'https://packagist.org/packages/'.$slug,
            ];
            $links[] = [
                'label' => 'Issues',
                'url' => rtrim($repository, '/').'/issues',
            ];
        }

        return $links;
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

        return $resolved !== false ? $resolved : rtrim(str_replace('\\', '/', $dir), '/');
    }

    /**
     * Resolve a path that may not exist yet (e.g. a fresh export directory).
     */
    public static function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $resolved = realpath($path);

        if ($resolved !== false) {
            return $resolved;
        }

        $parent = realpath(dirname($path));

        if ($parent !== false) {
            return $parent.'/'.basename($path);
        }

        return rtrim($path, '/');
    }

    /**
     * Relative URL/path from one directory to another (forward slashes).
     */
    public static function relativePath(string $fromDir, string $toPath): string
    {
        $from = self::normalizePath($fromDir);
        $to = self::normalizePath($toPath);

        $fromParts = array_values(array_filter(explode('/', $from), static fn (string $p): bool => $p !== ''));
        $toParts = array_values(array_filter(explode('/', $to), static fn (string $p): bool => $p !== ''));

        // Keep drive/root segment differences intact on Windows-style paths.
        while ($fromParts !== [] && $toParts !== [] && $fromParts[0] === $toParts[0]) {
            array_shift($fromParts);
            array_shift($toParts);
        }

        $prefix = str_repeat('../', count($fromParts));
        $suffix = implode('/', $toParts);

        if ($prefix === '' && $suffix === '') {
            return '.';
        }

        if ($prefix === '') {
            return $suffix;
        }

        if ($suffix === '') {
            return rtrim($prefix, '/');
        }

        return $prefix.$suffix;
    }
}
