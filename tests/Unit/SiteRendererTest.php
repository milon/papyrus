<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Render\Html\SiteRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SiteRendererTest extends TestCase
{
    #[Test]
    public function mini_book_builds_site_with_sidebar_and_chapters(): void
    {
        $fixture = dirname(__DIR__).'/fixtures/mini-book';
        $export = sys_get_temp_dir().'/papyrus-site-'.uniqid('', true);
        mkdir($export);

        $project = Project::load($fixture)->withExportDir($export);

        try {
            $siteDir = (new SiteRenderer($project))->render();

            $this->assertDirectoryExists($siteDir);
            $this->assertFileExists($siteDir.'/index.html');
            $this->assertFileExists($siteDir.'/00-copyright.html');
            $this->assertFileExists($siteDir.'/01-chapter-one.html');
            $this->assertFileExists($siteDir.'/assets/site.css');
            $this->assertFileExists($siteDir.'/assets/site.js');

            $index = file_get_contents($siteDir.'/index.html');
            $this->assertIsString($index);
            $this->assertStringContainsString('id="sidebar"', $index);
            $this->assertStringContainsString('01-chapter-one.html', $index);
            $this->assertStringContainsString('theme-toggle', $index);
            $this->assertStringContainsString('nav-toggle', $index);
            $this->assertStringContainsString('Mini Book', $index);

            $chapter = file_get_contents($siteDir.'/01-chapter-one.html');
            $this->assertIsString($chapter);
            $this->assertStringContainsString('<strong>world</strong>', $chapter);
            $this->assertStringContainsString('class="is-active"', $chapter);
            $this->assertStringContainsString('aria-current="page"', $chapter);
            $this->assertStringContainsString('chapter-nav', $chapter);

            $css = file_get_contents($siteDir.'/assets/site.css');
            $this->assertIsString($css);
            $this->assertStringContainsString('--bg: #ffffff', $css);
            $this->assertStringContainsString('html[data-theme="dark"]', $css);
            $this->assertStringContainsString('.sidebar', $css);
            $this->assertStringContainsString('@media (min-width: 56em)', $css);
            $this->assertFileExists($siteDir.'/.nojekyll');
        } finally {
            $this->removeDir($export);
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
