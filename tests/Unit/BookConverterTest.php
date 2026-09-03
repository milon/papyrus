<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Markdown\BookConverter;
use Milon\Papyrus\Render\Html\SiteRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BookConverterTest extends TestCase
{
    private string $fixtureDir;

    protected function setUp(): void
    {
        $this->fixtureDir = dirname(__DIR__).'/fixtures/mini-book';
    }

    #[Test]
    public function mini_book_converts_to_chapters_with_html(): void
    {
        $project = Project::load($this->fixtureDir);
        $book = $project->bookConverter()->convertDirectory($project->contentDir);

        $this->assertCount(2, $book->chapters);
        $this->assertCount(1, $book->preToc());
        $this->assertCount(1, $book->body());

        $copyright = $book->preToc()[0];
        $this->assertSame('00-copyright.md', $copyright->source);
        $this->assertTrue($copyright->pretoc);
        $this->assertSame('Copyright', $copyright->title());
        $this->assertStringContainsString('Papyrus fixture', $copyright->html);

        $chapter = $book->body()[0];
        $this->assertSame('01-chapter-one.md', $chapter->source);
        $this->assertFalse($chapter->pretoc);

        $beforeFirstHeading = ($position = strpos($chapter->html, '<h1>')) === false
            ? $chapter->html
            : substr($chapter->html, 0, $position);
        $this->assertStringNotContainsString('page-break-after: always', $beforeFirstHeading);

        $this->assertStringContainsString('<strong>world</strong>', $chapter->html);
        $this->assertStringContainsString("class='notice'", $chapter->html);
        $this->assertStringContainsString('class="caution"', $chapter->html);
        $this->assertStringContainsString('বন্ধন', $chapter->html);
    }

    #[Test]
    public function it_renders_asides_and_skips_mermaid_highlighting(): void
    {
        $markdown = <<<'MD'
---
title: Features
---

# Features

:::tip[Pro tip]
Remember to save.
:::

```mermaid
flowchart TD
  A --> B
```

```php
echo 'hi';
```
MD;

        $dir = sys_get_temp_dir().'/papyrus-markdown-'.uniqid('', true);
        mkdir($dir);
        file_put_contents($dir.'/chapter.md', $markdown);

        try {
            $book = (new BookConverter)->convertDirectory($dir);
            $html = $book->chapters[0]->html;

            $this->assertStringContainsString('class="tip"', $html);
            $this->assertStringContainsString('Pro tip', $html);
            $this->assertStringContainsString('language-mermaid', $html);
            $this->assertStringContainsString('flowchart TD', $html);
            $this->assertStringContainsString('hljs', $html);
        } finally {
            unlink($dir.'/chapter.md');
            rmdir($dir);
        }
    }

    #[Test]
    public function it_applies_break_level_from_converter(): void
    {
        $markdown = <<<'MD'
---
title: Two
---

## Section
MD;

        $dir = sys_get_temp_dir().'/papyrus-break-'.uniqid('', true);
        mkdir($dir);
        file_put_contents($dir.'/01-one.md', "# One\n");
        file_put_contents($dir.'/02-two.md', $markdown);

        try {
            $book = (new BookConverter(breakLevel: 2))->convertDirectory($dir);
            $second = $book->chapters[1]->html;

            $this->assertStringContainsString('page-break-after: always', $second);
            $this->assertMatchesRegularExpression('/page-break-after.*<h2>/s', $second);
        } finally {
            unlink($dir.'/01-one.md');
            unlink($dir.'/02-two.md');
            rmdir($dir);
        }
    }

    #[Test]
    public function draft_chapters_are_excluded_unless_include_drafts_is_set(): void
    {
        $bookDir = sys_get_temp_dir().'/papyrus-draft-'.uniqid('', true);
        $export = $bookDir.'/export';
        mkdir($bookDir.'/content', 0755, true);
        mkdir($bookDir.'/assets', 0755, true);
        mkdir($export, 0755, true);

        file_put_contents($bookDir.'/content/01-published.md', "---\ntitle: Published\n---\n\n# Published\n\nHello.\n");
        file_put_contents($bookDir.'/content/02-wip.md', "---\ntitle: Work in progress\ndraft: true\n---\n\n# WIP\n\nSecret.\n");
        file_put_contents($bookDir.'/papyrus.php', <<<'PHP'
<?php

return [
    'title' => 'Draft Book',
    'author' => 'Author',
    'themes' => ['light'],
    'mermaid' => ['enabled' => false],
];
PHP);

        try {
            $project = Project::load($bookDir)->withExportDir($export);
            $published = $project->bookWithFigures(breakLevel: 1, exportTheme: 'html');
            $this->assertCount(1, $published->chapters);
            $this->assertSame('01-published.md', $published->chapters[0]->source);

            $withDrafts = $project->withIncludeDrafts()->bookWithFigures(breakLevel: 1, exportTheme: 'html');
            $this->assertCount(2, $withDrafts->chapters);
            $this->assertTrue($withDrafts->chapters[1]->isDraft());

            $siteDir = (new SiteRenderer($project))->render();
            $this->assertFileExists($siteDir.'/01-published.html');
            $this->assertFileDoesNotExist($siteDir.'/02-wip.html');

            $draftSite = (new SiteRenderer($project->withIncludeDrafts()))->render($export.'/with-drafts-site');
            $this->assertFileExists($draftSite.'/02-wip.html');
        } finally {
            $this->removeDir($bookDir);
        }
    }

    private function removeDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($dir);
    }
}
