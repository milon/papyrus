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
            $this->assertFileDoesNotExist($siteDir.'/CNAME');
            $this->assertFileExists($siteDir.'/404.html');
            $this->assertFileExists($siteDir.'/sitemap.xml');
            $this->assertFileExists($siteDir.'/robots.txt');
            $this->assertFileExists($siteDir.'/assets/search.json');
            $this->assertStringContainsString('id="site-search-input"', $index);

            $sitemap = file_get_contents($siteDir.'/sitemap.xml');
            $this->assertIsString($sitemap);
            $this->assertStringContainsString('<loc>/index.html</loc>', $sitemap);
            $this->assertStringContainsString('<loc>/01-chapter-one.html</loc>', $sitemap);

            $notFound = file_get_contents($siteDir.'/404.html');
            $this->assertIsString($notFound);
            $this->assertStringContainsString('Page not found', $notFound);
            $this->assertStringContainsString('href="index.html"', $notFound);
            $this->assertStringContainsString('name="robots"', $notFound);
        } finally {
            $this->removeDir($export);
        }
    }

    #[Test]
    public function site_index_includes_banner_and_configured_links(): void
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
        'lead' => 'A short lead for the home page.',
        'cname' => 'https://Docs.Example.com/',
        'links' => [
            ['label' => 'Project', 'url' => 'https://example.com/project'],
            ['label' => 'Downloads', 'chapter' => '19-downloads'],
        ],
    ],
    'mermaid' => ['enabled' => false],
];
PHP);
        file_put_contents($bookDir.'/assets/theme-html.html', '<html><body>{{$body}}</body></html>');
        file_put_contents($bookDir.'/content/19-downloads.md', "---\ntitle: Downloads\n---\n\nPDFs here.\n");

        try {
            $project = Project::load($bookDir)->withExportDir($export);
            $siteDir = (new SiteRenderer($project))->render();
            $index = file_get_contents($siteDir.'/index.html');
            $this->assertIsString($index);
            $this->assertFileExists($siteDir.'/assets/banner.jpg');
            $this->assertFileExists($siteDir.'/19-downloads.html');
            $this->assertStringContainsString('class="book-banner"', $index);
            $this->assertStringContainsString('src="assets/banner.jpg"', $index);
            $this->assertStringContainsString('A short lead for the home page.', $index);
            $this->assertStringContainsString('https://example.com/project', $index);
            $this->assertStringContainsString('Project', $index);
            $this->assertStringContainsString('href="19-downloads.html">Downloads</a>', $index);
            $this->assertStringNotContainsString('Source on GitHub', $index);
            $this->assertFileExists($siteDir.'/CNAME');
            $this->assertSame("docs.example.com\n", file_get_contents($siteDir.'/CNAME'));
            $sitemap = file_get_contents($siteDir.'/sitemap.xml');
            $this->assertIsString($sitemap);
            $this->assertStringContainsString('<loc>https://docs.example.com/index.html</loc>', $sitemap);
            $robots = file_get_contents($siteDir.'/robots.txt');
            $this->assertIsString($robots);
            $this->assertStringContainsString('Sitemap: https://docs.example.com/sitemap.xml', $robots);
        } finally {
            $this->removeDir($bookDir);
            $this->removeDir($export);
        }
    }

    #[Test]
    public function site_index_uses_repository_as_backward_compatible_default_links(): void
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
            preg_match('/<p class="home-links">.*?<\/p>/s', $index, $matches);
            $homeLinks = $matches[0] ?? '';
            $this->assertStringContainsString('Source on GitHub', $index);
            $this->assertStringContainsString('Packagist', $index);
            $this->assertStringContainsString('Issues', $index);
            $this->assertStringNotContainsString('href="19-downloads.html">Downloads</a>', $homeLinks);
        } finally {
            $this->removeDir($bookDir);
            $this->removeDir($export);
        }
    }

    #[Test]
    public function site_index_includes_base_href_when_base_path_is_configured(): void
    {
        $bookDir = sys_get_temp_dir().'/papyrus-site-base-'.uniqid('', true);
        $export = sys_get_temp_dir().'/papyrus-site-base-export-'.uniqid('', true);
        mkdir($bookDir.'/content', 0755, true);
        mkdir($bookDir.'/assets', 0755, true);
        mkdir($export);

        file_put_contents($bookDir.'/content/01.md', "---\ntitle: One\n---\n\nHello.\n");
        file_put_contents($bookDir.'/papyrus.php', <<<'PHP'
<?php

return [
    'title' => 'Base Path Book',
    'author' => 'Author',
    'themes' => ['light'],
    'site' => [
        'base_path' => 'docs/book',
    ],
    'mermaid' => ['enabled' => false],
];
PHP);

        try {
            $project = Project::load($bookDir)->withExportDir($export);
            $siteDir = (new SiteRenderer($project))->render();
            $index = file_get_contents($siteDir.'/index.html');
            $this->assertIsString($index);
            $this->assertStringContainsString('<base href="/docs/book/">', $index);
            $this->assertSame('/docs/book', $project->siteBasePath());
            $sitemap = file_get_contents($siteDir.'/sitemap.xml');
            $this->assertIsString($sitemap);
            $this->assertStringContainsString('<loc>/docs/book/index.html</loc>', $sitemap);
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
