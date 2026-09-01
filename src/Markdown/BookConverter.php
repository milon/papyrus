<?php

declare(strict_types=1);

namespace Milon\Papyrus\Markdown;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;
use League\CommonMark\MarkdownConverter;
use Milon\Papyrus\Book\Book;
use Milon\Papyrus\Book\Chapter;
use Milon\Papyrus\Cache\CachedChapter;
use Milon\Papyrus\Cache\ChapterHtmlCache;
use Milon\Papyrus\Markdown\Extensions\Aside;
use Milon\Papyrus\Markdown\Extensions\AsideExtension;
use Milon\Papyrus\Markdown\Extensions\AsideRenderer;
use Spatie\CommonMarkHighlighter\FencedCodeRenderer;
use Spatie\CommonMarkHighlighter\IndentedCodeRenderer;

final class MarkdownConverterFactory
{
    /**
     * @param  list<string>  $highlightLanguages
     * @param  (callable(Environment): void)|null  $configure
     */
    public static function create(
        array $highlightLanguages = ['html', 'php', 'js', 'bash', 'json'],
        ?callable $configure = null,
    ): MarkdownConverter {
        $environment = new Environment([]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new FrontMatterExtension);
        $environment->addExtension(new AsideExtension);
        $environment->addExtension(new AttributesExtension);
        $environment->addExtension(new TaskListExtension);

        $environment->addRenderer(
            FencedCode::class,
            new SkippingFencedCodeRenderer(new FencedCodeRenderer($highlightLanguages)),
        );
        $environment->addRenderer(
            IndentedCode::class,
            new IndentedCodeRenderer($highlightLanguages),
        );
        $environment->addRenderer(Aside::class, new AsideRenderer);

        if ($configure !== null) {
            $configure($environment);
        }

        return new MarkdownConverter($environment);
    }
}

final class BookConverter
{
    public function __construct(
        private readonly int $breakLevel = 2,
        private $configureCommonMark = null,
        private readonly ?ChapterHtmlCache $cache = null,
        private readonly string $configHash = '',
    ) {}

    public function convertDirectory(string $contentDir): Book
    {
        if (! is_dir($contentDir)) {
            throw new MarkdownException(sprintf('Content directory not found: %s', $contentDir));
        }

        $files = $this->discoverMarkdownFiles($contentDir);
        $converter = MarkdownConverterFactory::create(configure: $this->configureCommonMark);
        $postProcessor = new HtmlPostProcessor($this->breakLevel);

        $chapters = [];
        $bodyChapterIndex = 0;

        foreach ($files as $index => $file) {
            $markdown = file_get_contents($file);

            if ($markdown === false) {
                throw new MarkdownException(sprintf('Could not read: %s', $file));
            }

            $relative = ltrim(str_replace($contentDir, '', $file), '/');
            $contentHash = ChapterHtmlCache::contentHash($markdown);
            $cached = $this->cache?->get($relative, $contentHash, $this->configHash, $this->breakLevel);

            if ($cached !== null) {
                $pretoc = $cached->pretoc;
                $frontMatter = $cached->frontMatter;
                $breakIndex = $pretoc ? 0 : $bodyChapterIndex;
                $html = $postProcessor->process($cached->rawHtml, $breakIndex);
            } else {
                $converted = $converter->convert($markdown);
                $frontMatter = [];

                if ($converted instanceof RenderedContentWithFrontMatter) {
                    $frontMatter = $converted->getFrontMatter();
                }

                $pretoc = $this->isPretoc($frontMatter);
                $breakIndex = $pretoc ? 0 : $bodyChapterIndex;
                $rawHtml = $converted->getContent();
                $html = $postProcessor->process($rawHtml, $breakIndex);

                $this->cache?->put(
                    $relative,
                    $contentHash,
                    $this->configHash,
                    $this->breakLevel,
                    new CachedChapter($rawHtml, is_array($frontMatter) ? $frontMatter : [], $pretoc),
                );
            }

            if (! $pretoc) {
                $bodyChapterIndex++;
            }

            $chapters[] = new Chapter(
                source: $relative,
                path: $file,
                frontMatter: is_array($frontMatter) ? $frontMatter : [],
                html: $html,
                pretoc: $pretoc,
            );
        }

        return new Book($chapters);
    }

    /**
     * @return list<string>
     */
    private function discoverMarkdownFiles(string $contentDir): array
    {
        $contentDir = rtrim($contentDir, '/\\');
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($contentDir, \FilesystemIterator::SKIP_DOTS),
        );

        $files = [];

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'md') {
                $files[] = $file->getPathname();
            }
        }

        sort($files, SORT_NATURAL);

        return $files;
    }

    /**
     * @param  array<string, mixed>|null  $frontMatter
     */
    private function isPretoc(?array $frontMatter): bool
    {
        if ($frontMatter === null) {
            return false;
        }

        $pretoc = $frontMatter['pretoc'] ?? false;

        return $pretoc === true || $pretoc === 'true' || $pretoc === 1 || $pretoc === '1';
    }
}

final class MarkdownException extends \RuntimeException {}
