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
            $this->assertStringContainsString('Start reading', $index);

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
            $this->assertStringContainsString('.book-banner', $css);
            $this->assertStringContainsString('@media (min-width: 56em)', $css);
            $this->assertFileExists($siteDir.'/.nojekyll');
        } finally {
            $this->removeDir($export);
        }
    }

    #[Test]
    public function site_index_includes_banner_and_repository_links(): void
    {
        $bookDir = sys_get_temp_dir().'/papyrus-site-home-'.uniqid('', true);
        $export = sys_get_temp_dir().'/papyrus-site-home-export-'.uniqid('', true);
        mkdir($bookDir.'/content', 0755, true);
        mkdir($bookDir.'/assets/fonts', 0755, true);
        mkdir($export);

        file_put_contents($bookDir.'/content/01.md', "---\ntitle: One\n---\n\nHello.\n");
        file_put_contents($bookDir.'/assets/banner.jpg', 'fake-image');
        file_put_contents($bookDir.'/papyrus.php', <<<'PHP'
<?php

return [
    'title' => 'Banner Book',
    'subtitle' => 'A demo',
    'author' => 'Author',
    'themes' => ['light'],
    'site' => [
        'banner' => 'banner.jpg',
        'repository' => 'https://github.com/milon/papyrus',
        'lead' => 'A short lead for the home page.',
    ],
    'mermaid' => ['enabled' => false],
];
PHP);
        file_put_contents($bookDir.'/assets/theme-html.html', '<html><body>{{$body}}</body></html>');

        try {
            $project = Project::load($bookDir)->withExportDir($export);
            $siteDir = (new SiteRenderer($project))->render();
            $index = file_get_contents($siteDir.'/index.html');
            $this->assertIsString($index);
            $this->assertFileExists($siteDir.'/assets/banner.jpg');
            $this->assertStringContainsString('class="book-banner"', $index);
            $this->assertStringContainsString('src="assets/banner.jpg"', $index);
            $this->assertStringContainsString('A short lead for the home page.', $index);
            $this->assertStringContainsString('https://github.com/milon/papyrus', $index);
            $this->assertStringContainsString('Source on GitHub', $index);
            $this->assertStringContainsString('Packagist', $index);
            $this->assertStringContainsString('Issues', $index);
        } finally {
            $this->removeDir($bookDir);
            $this->removeDir($export);
        }
    }

    #[Test]
    public function site_index_links_to_downloads_chapter_when_present(): void
    {
        $bookDir = sys_get_temp_dir().'/papyrus-site-downloads-'.uniqid('', true);
        $export = sys_get_temp_dir().'/papyrus-site-downloads-export-'.uniqid('', true);
        mkdir($bookDir.'/content', 0755, true);
        mkdir($bookDir.'/assets', 0755, true);
        mkdir($export);

        file_put_contents($bookDir.'/content/01.md', "---\ntitle: One\n---\n\nHello.\n");
        file_put_contents($bookDir.'/content/19-downloads.md', "---\ntitle: Downloads\n---\n\nPDFs here.\n");
        file_put_contents($bookDir.'/papyrus.php', <<<'PHP'
<?php

return [
    'title' => 'Downloads Book',
    'author' => 'Author',
    'themes' => ['light'],
    'site' => [
        'repository' => 'https://github.com/milon/papyrus',
    ],
    'mermaid' => ['enabled' => false],
];
PHP);
        file_put_contents($bookDir.'/assets/theme-html.html', '<html><body>{{$body}}</body></html>');

        try {
            $project = Project::load($bookDir)->withExportDir($export);
            $siteDir = (new SiteRenderer($project))->render();
            $index = file_get_contents($siteDir.'/index.html');
            $this->assertIsString($index);
            $this->assertFileExists($siteDir.'/19-downloads.html');
            $this->assertStringContainsString('href="19-downloads.html">Downloads</a>', $index);
            $this->assertStringContainsString('Source on GitHub', $index);
        } finally {
            $this->removeDir($bookDir);
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
