<?php

declare(strict_types=1);

namespace Milon\Papyrus\Mermaid;

use Milon\Papyrus\Book\Book;
use Milon\Papyrus\Book\Chapter;
use Milon\Papyrus\Config\MermaidConfig;
use Milon\Papyrus\Config\Project;

final class MermaidRenderer
{
    public function __construct(
        private readonly Project $project,
        private readonly MermaidCli $cli,
        private readonly MermaidCache $cache,
        private readonly MermaidConfig $config,
    ) {}

    /**
     * @return array{content: string, cached: bool}
     */
    public function renderDiagram(string $diagram, string $exportTheme): array
    {
        $fullWidth = $exportTheme === 'html';

        if ($this->config->isDualExport($exportTheme)) {
            return $this->renderDualDiagram($diagram, $fullWidth);
        }

        if ($this->config->usesBookPalette()) {
            $variant = $this->config->bookVariant($exportTheme);

            return $this->renderSingleDiagram($diagram, null, $variant, MermaidBookPalette::config($variant), $fullWidth);
        }

        return $this->renderSingleDiagram(
            $diagram,
            $this->config->resolvedTheme($exportTheme),
            null,
            null,
            $fullWidth,
        );
    }

    public function processBook(Book $book, string $exportTheme): Book
    {
        if (! $this->config->enabled) {
            return $book;
        }

        $chapters = [];

        foreach ($book->chapters as $chapter) {
            $markdown = file_get_contents($chapter->path);

            if ($markdown === false) {
                throw new MermaidException(sprintf('Unable to read chapter for Mermaid processing: %s', $chapter->path));
            }

            $chapters[] = new Chapter(
                source: $chapter->source,
                path: $chapter->path,
                frontMatter: $chapter->frontMatter,
                html: $this->processChapterHtml($chapter->html, $markdown, $chapter->path, $exportTheme),
                pretoc: $chapter->pretoc,
            );
        }

        return new Book($chapters);
    }

    /**
     * @param  array{theme: string, themeVariables: array<string, string>, flowchart: array<string, mixed>}|null  $bookConfig
     * @return array{content: string, cached: bool}
     */
    private function renderSingleDiagram(string $diagram, ?string $cliTheme, ?string $variant, ?array $bookConfig, bool $fullWidth): array
    {
        $version = $this->cli->version() ?? 'unknown';
        $key = hash('sha256', json_encode([
            $diagram,
            $cliTheme,
            $variant,
            $bookConfig,
            $this->config->format,
            $version,
            'v2',
        ], JSON_THROW_ON_ERROR));

        $extension = $this->config->format;
        $cachedPath = $this->cache->path($key, $extension);
        $domId = 'mermaid-'.$key;

        if ($this->cache->has($key, $extension)) {
            return [
                'content' => $this->figureHtml([$variant ?? 'single' => $cachedPath], [$domId], dual: false, fullWidth: $fullWidth),
                'cached' => true,
            ];
        }

        $this->renderToCache($diagram, $cachedPath, $cliTheme, $bookConfig);

        return [
            'content' => $this->figureHtml([$variant ?? 'single' => $cachedPath], [$domId], dual: false, fullWidth: $fullWidth),
            'cached' => false,
        ];
    }

    /**
     * @return array{content: string, cached: bool}
     */
    private function renderDualDiagram(string $diagram, bool $fullWidth): array
    {
        $version = $this->cli->version() ?? 'unknown';
        $baseKey = hash('sha256', json_encode([
            $diagram,
            'dual',
            MermaidBookPalette::config('light'),
            MermaidBookPalette::config('dark'),
            $this->config->format,
            $version,
            'v2',
        ], JSON_THROW_ON_ERROR));

        $extension = $this->config->format;
        $lightKey = $baseKey.'-light';
        $darkKey = $baseKey.'-dark';
        $lightPath = $this->cache->path($lightKey, $extension);
        $darkPath = $this->cache->path($darkKey, $extension);
        $lightId = 'mermaid-'.$lightKey;
        $darkId = 'mermaid-'.$darkKey;

        $lightCached = $this->cache->has($lightKey, $extension);
        $darkCached = $this->cache->has($darkKey, $extension);

        if (! $lightCached) {
            $this->renderToCache($diagram, $lightPath, null, MermaidBookPalette::config('light'));
        }

        if (! $darkCached) {
            $this->renderToCache($diagram, $darkPath, null, MermaidBookPalette::config('dark'));
        }

        return [
            'content' => $this->figureHtml(
                ['light' => $lightPath, 'dark' => $darkPath],
                [$lightId, $darkId],
                dual: true,
                fullWidth: $fullWidth,
            ),
            'cached' => $lightCached && $darkCached,
        ];
    }

    /**
     * @param  array{theme: string, themeVariables: array<string, string>, flowchart: array<string, mixed>}|null  $bookConfig
     */
    private function renderToCache(string $diagram, string $cachedPath, ?string $cliTheme, ?array $bookConfig): void
    {
        $this->cache->ensureDirectory();

        $inputPath = $cachedPath.'.mmd';
        $configPath = null;

        file_put_contents($inputPath, $diagram);

        try {
            if ($bookConfig !== null) {
                $configPath = $cachedPath.'.config.json';
                file_put_contents($configPath, json_encode($bookConfig, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
            }

            $this->cli->render($inputPath, $cachedPath, $cliTheme, $configPath);
        } finally {
            if (is_file($inputPath)) {
                unlink($inputPath);
            }

            if ($configPath !== null && is_file($configPath)) {
                unlink($configPath);
            }
        }
    }

    private function processChapterHtml(string $html, string $markdown, string $sourcePath, string $exportTheme): string
    {
        $blocks = MermaidBlockExtractor::fromMarkdown($markdown);

        if ($blocks === []) {
            return $html;
        }

        $pattern = '/<pre><code class="language-mermaid"[^>]*>.*?<\/code><\/pre>/s';
        $index = 0;

        return preg_replace_callback(
            $pattern,
            function (array $matches) use ($blocks, &$index, $sourcePath, $exportTheme): string {
                $block = $blocks[$index] ?? null;
                $index++;

                if ($block === null) {
                    return $matches[0];
                }

                try {
                    $rendered = $this->renderDiagram($block['body'], $exportTheme);

                    return $rendered['content'];
                } catch (MermaidException $e) {
                    throw new MermaidException(sprintf(
                        '%s:%d: %s',
                        $sourcePath,
                        $block['line'],
                        $e->getMessage(),
                    ), previous: $e);
                }
            },
            $html,
        ) ?? $html;
    }

    /**
     * @param  array<string, string>  $paths  variant => absolute path
     * @param  list<string>  $domIds
     */
    private function figureHtml(array $paths, array $domIds, bool $dual, bool $fullWidth): string
    {
        $style = $fullWidth
            ? null
            : sprintf('max-width: %smm; margin: 0 auto;', $this->config->maxWidthMm);

        $styleAttr = $style !== null ? sprintf(' style="%s"', $style) : '';

        if ($dual) {
            $light = $this->embedAsset($paths['light'], $domIds[0], $fullWidth);
            $dark = $this->embedAsset($paths['dark'], $domIds[1], $fullWidth);

            return sprintf(
                '<figure class="mermaid"%s><div class="mermaid-light">%s</div><div class="mermaid-dark">%s</div></figure>',
                $styleAttr,
                $light,
                $dark,
            );
        }

        $path = array_values($paths)[0];
        $body = $this->embedAsset($path, $domIds[0], $fullWidth);

        return sprintf('<figure class="mermaid"%s>%s</figure>', $styleAttr, $body);
    }

    private function embedAsset(string $cachedPath, string $domId, bool $fullWidth): string
    {
        if ($this->config->format === 'svg') {
            $svg = file_get_contents($cachedPath);

            if ($svg === false || trim($svg) === '') {
                throw new MermaidException('Mermaid SVG output was empty');
            }

            $svg = preg_replace('/<\?xml[^?]*\?>\s*/', '', $svg) ?? $svg;
            $svg = $this->rewriteSvgId($svg, $domId);

            if ($fullWidth) {
                $svg = $this->makeSvgFullWidth($svg);
            }

            return $svg;
        }

        $absolutePath = realpath($cachedPath) ?: $cachedPath;

        return sprintf(
            '<img src="%s" alt="Diagram" style="width: 100%%; height: auto;"/>',
            htmlspecialchars($absolutePath, ENT_QUOTES | ENT_HTML5),
        );
    }

    private function makeSvgFullWidth(string $svg): string
    {
        $svg = preg_replace('/\s*max-width:\s*[^;"\']+;?/i', '', $svg) ?? $svg;
        $svg = preg_replace('/\s*style="\s*"/', '', $svg) ?? $svg;

        if (preg_match('/<svg\b[^>]*\bwidth="/', $svg) === 1) {
            $svg = preg_replace('/\bwidth="[^"]*"/', 'width="100%"', $svg, 1) ?? $svg;
        } else {
            $svg = preg_replace('/<svg\b/', '<svg width="100%"', $svg, 1) ?? $svg;
        }

        return $svg;
    }

    private function rewriteSvgId(string $svg, string $domId): string
    {
        if (preg_match('/<svg\b[^>]*\bid="([^"]+)"/', $svg, $matches) === 1) {
            $oldId = $matches[1];
            $svg = str_replace('id="'.$oldId.'"', 'id="'.$domId.'"', $svg);
            $svg = str_replace('#'.$oldId, '#'.$domId, $svg);

            return $svg;
        }

        return preg_replace('/<svg\b/', '<svg id="'.$domId.'"', $svg, 1) ?? $svg;
    }
}
