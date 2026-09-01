<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Markdown\BookConverter;
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
}
