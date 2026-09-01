<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Cache\CachedChapter;
use Milon\Papyrus\Cache\ChapterHtmlCache;
use Milon\Papyrus\Commands\LintCommand;
use Milon\Papyrus\Lint\CodeFenceLinter;
use Milon\Papyrus\Watch\ProjectWatcher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class LintCommandTest extends TestCase
{
    #[Test]
    public function linter_flags_open_tag_and_over_width_lines(): void
    {
        $markdown = <<<'MD'
# Chapter

```php
<?php

// first
// second
$this->isAVeryLongLineThatDefinitelyExceedsTheConfiguredMaximumWidthForPhpFencesInBooks();
```
MD;

        $file = sys_get_temp_dir().'/papyrus-lint-'.uniqid('', true).'.md';
        file_put_contents($file, $markdown);

        try {
            $result = (new CodeFenceLinter(66))->lintFile($file);

            $this->assertCount(1, $result->issues);
            $this->assertCount(1, $result->fixed);
            $this->assertStringContainsString('removed <?php tag', $result->fixed[0]);
        } finally {
            unlink($file);
        }
    }

    #[Test]
    public function lint_command_exits_zero_for_clean_fixture(): void
    {
        $fixtureDir = dirname(__DIR__).'/fixtures/mini-book';
        $tester = new CommandTester(new LintCommand);
        $exitCode = $tester->execute(['--dir' => $fixtureDir]);

        $this->assertSame(0, $exitCode);
    }
}

final class ChapterHtmlCacheTest extends TestCase
{
    #[Test]
    public function cache_round_trips_and_invalidates_on_content_change(): void
    {
        $dir = sys_get_temp_dir().'/papyrus-cache-'.uniqid('', true);
        mkdir($dir);
        $cache = new ChapterHtmlCache($dir);

        $chapter = new CachedChapter('<p>Hello</p>', ['title' => 'One'], false);

        try {
            $cache->put('01-one.md', 'hash-a', 'config-a', 2, $chapter);
            $loaded = $cache->get('01-one.md', 'hash-a', 'config-a', 2);

            $this->assertNotNull($loaded);
            $this->assertSame('<p>Hello</p>', $loaded->rawHtml);

            $this->assertNull($cache->get('01-one.md', 'hash-b', 'config-a', 2));
            $this->assertNull($cache->get('01-one.md', 'hash-a', 'config-b', 2));
            $this->assertNull($cache->get('01-one.md', 'hash-a', 'config-a', 1));
        } finally {
            array_map('unlink', glob($dir.'/*') ?: []);
            rmdir($dir);
        }
    }
}

final class ProjectWatcherTest extends TestCase
{
    #[Test]
    public function watcher_detects_modified_files(): void
    {
        $dir = sys_get_temp_dir().'/papyrus-watch-'.uniqid('', true);
        mkdir($dir);
        $file = $dir.'/chapter.md';
        file_put_contents($file, 'hello');

        $watcher = new ProjectWatcher;
        $paths = [$file];
        $before = $watcher->snapshot($paths);

        sleep(1);
        touch($file);
        $after = $watcher->snapshot($paths);

        $this->assertNotSame([], $watcher->changedFiles($before, $after));

        unlink($file);
        rmdir($dir);
    }
}
