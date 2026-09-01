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
        $theme = $this->config->resolvedTheme($exportTheme);
        $version = $this->cli->version() ?? 'unknown';
        $key = hash('sha256', json_encode([
            $diagram,
            $theme,
            $this->config->format,
            $version,
        ], JSON_THROW_ON_ERROR));

        $extension = $this->config->format;
        $cachedPath = $this->cache->path($key, $extension);

        if ($this->cache->has($key, $extension)) {
            return [
                'content' => $this->figureHtml($cachedPath),
                'cached' => true,
            ];
        }

        $this->cache->ensureDirectory();

        $inputPath = $this->cache->path($key, 'mmd');
        file_put_contents($inputPath, $diagram);

        try {
            $this->cli->render($inputPath, $cachedPath, $theme);
        } finally {
            if (is_file($inputPath)) {
                unlink($inputPath);
            }
        }

        return [
            'content' => $this->figureHtml($cachedPath),
            'cached' => false,
        ];
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

    private function figureHtml(string $cachedPath): string
    {
        $style = sprintf('max-width: %smm; margin: 0 auto;', $this->config->maxWidthMm);

        if ($this->config->format === 'svg') {
            $svg = file_get_contents($cachedPath);

            if ($svg === false || trim($svg) === '') {
                throw new MermaidException('Mermaid SVG output was empty');
            }

            $svg = preg_replace('/<\?xml[^?]*\?>\s*/', '', $svg) ?? $svg;

            return sprintf('<figure class="mermaid" style="%s">%s</figure>', $style, $svg);
        }

        $absolutePath = realpath($cachedPath) ?: $cachedPath;

        return sprintf(
            '<figure class="mermaid" style="%s"><img src="%s" alt="Diagram" style="width: 100%%; height: auto;"/></figure>',
            $style,
            htmlspecialchars($absolutePath, ENT_QUOTES | ENT_HTML5),
        );
    }
}
