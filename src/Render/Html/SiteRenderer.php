<?php

declare(strict_types=1);

namespace Milon\Papyrus\Render\Html;

use Milon\Papyrus\Book\Chapter;
use Milon\Papyrus\Config\Project;

final class SiteRenderer
{
    public function __construct(
        private readonly Project $project,
    ) {}

    public function render(?string $outputDir = null): string
    {
        $book = $this->project->bookWithFigures(breakLevel: 1, exportTheme: 'html');
        $chapters = $book->chapters;

        if ($chapters === []) {
            throw new HtmlException('No chapters found to build a site.');
        }

        if (! is_dir($this->project->exportDir)) {
            mkdir($this->project->exportDir, 0755, true);
        }

        $siteDir = $outputDir ?? sprintf(
            '%s/%s-site',
            $this->project->exportDir,
            $this->project->outputSlug(),
        );

        if (! is_dir($siteDir) && ! mkdir($siteDir, 0755, true) && ! is_dir($siteDir)) {
            throw new HtmlException(sprintf('Unable to create site directory: %s', $siteDir));
        }

        $assetsDir = $siteDir.'/assets';

        if (! is_dir($assetsDir) && ! mkdir($assetsDir, 0755, true) && ! is_dir($assetsDir)) {
            throw new HtmlException(sprintf('Unable to create site assets directory: %s', $assetsDir));
        }

        $this->copyFontsIntoSite($assetsDir.'/fonts');
        $this->copySiteBanner($assetsDir);

        $css = WebTheme::fontFaceCss('fonts/')
            ."\n"
            .WebTheme::contentCss()
            ."\n"
            .WebTheme::siteLayoutCss();

        $this->writeFile($assetsDir.'/site.css', $css);
        $this->writeFile($assetsDir.'/site.js', WebTheme::themeScript());
        $this->writeFile($siteDir.'/.nojekyll', '');

        /** @var list<array{chapter: Chapter, file: string, title: string}> $pages */
        $pages = [];

        foreach ($chapters as $chapter) {
            $pages[] = [
                'chapter' => $chapter,
                'file' => $chapter->webSlug().'.html',
                'title' => $chapter->displayTitle(),
            ];
        }

        $this->writeFile($siteDir.'/index.html', $this->renderIndex($pages));

        foreach ($pages as $index => $page) {
            $prev = $pages[$index - 1] ?? null;
            $next = $pages[$index + 1] ?? null;
            $html = $this->renderChapterPage($pages, $page, $prev, $next);
            $this->writeFile($siteDir.'/'.$page['file'], $html);
        }

        return $siteDir;
    }

    /**
     * @param  list<array{chapter: Chapter, file: string, title: string}>  $pages
     */
    private function renderIndex(array $pages): string
    {
        $first = $pages[0];
        $title = htmlspecialchars($this->project->title(), ENT_QUOTES | ENT_HTML5);
        $subtitle = htmlspecialchars($this->project->subtitle(), ENT_QUOTES | ENT_HTML5);
        $author = htmlspecialchars($this->project->author(), ENT_QUOTES | ENT_HTML5);
        $firstFile = htmlspecialchars($first['file'], ENT_QUOTES | ENT_HTML5);
        $firstTitle = htmlspecialchars($first['title'], ENT_QUOTES | ENT_HTML5);

        $bannerHtml = '';
        $banner = $this->project->siteBanner();

        if ($banner !== null && is_file($this->project->assetsDir.'/'.$banner)) {
            $bannerHtml = sprintf(
                '<p class="book-banner"><img src="assets/%s" alt="%s"/></p>',
                htmlspecialchars($banner, ENT_QUOTES | ENT_HTML5),
                $title,
            );
        }

        $leadHtml = '';
        $lead = $this->project->siteLead();

        if ($lead !== null) {
            $leadHtml = '<p class="book-lead">'.htmlspecialchars($lead, ENT_QUOTES | ENT_HTML5).'</p>';
        }

        $linksHtml = $this->homeLinksHtml($pages);

        $body = <<<HTML
<div class="title-page">
    {$bannerHtml}
    <h1 class="book-title">{$title}</h1>
    <p class="book-subtitle">{$subtitle}</p>
    {$leadHtml}
    <p class="book-author">{$author}</p>
    {$linksHtml}
    <a class="start-reading" href="{$firstFile}">Start reading — {$firstTitle}</a>
</div>
HTML;

        return $this->document(
            pages: $pages,
            pageTitle: $this->project->title(),
            activeFile: 'index.html',
            body: $body,
            topbarTitle: $this->project->title(),
        );
    }

    /**
     * @param  list<array{chapter: Chapter, file: string, title: string}>  $pages
     */
    private function homeLinksHtml(array $pages): string
    {
        $items = '';

        foreach ($pages as $page) {
            if (strcasecmp($page['title'], 'Downloads') !== 0) {
                continue;
            }

            $items .= sprintf(
                '<a href="%s">Downloads</a>',
                htmlspecialchars($page['file'], ENT_QUOTES | ENT_HTML5),
            );
            break;
        }

        $repository = $this->project->siteRepository();

        if ($repository !== null) {
            $repoUrl = htmlspecialchars($repository, ENT_QUOTES | ENT_HTML5);
            $items .= sprintf('<a href="%s">Source on GitHub</a>', $repoUrl);

            if (preg_match('#github\.com/([^/]+/[^/]+?)(?:\.git)?/?$#', $repository, $matches) === 1) {
                $slug = $matches[1];
                $packagist = htmlspecialchars('https://packagist.org/packages/'.$slug, ENT_QUOTES | ENT_HTML5);
                $issues = htmlspecialchars(rtrim($repository, '/').'/issues', ENT_QUOTES | ENT_HTML5);
                $items .= sprintf('<a href="%s">Packagist</a>', $packagist);
                $items .= sprintf('<a href="%s">Issues</a>', $issues);
            }
        }

        if ($items === '') {
            return '';
        }

        return '<p class="home-links">'.$items.'</p>';
    }

    /**
     * @param  list<array{chapter: Chapter, file: string, title: string}>  $pages
     * @param  array{chapter: Chapter, file: string, title: string}  $page
     * @param  array{chapter: Chapter, file: string, title: string}|null  $prev
     * @param  array{chapter: Chapter, file: string, title: string}|null  $next
     */
    private function renderChapterPage(array $pages, array $page, ?array $prev, ?array $next): string
    {
        $nav = '<nav class="chapter-nav" aria-label="Chapter">';

        if ($prev !== null) {
            $nav .= sprintf(
                '<a class="prev" href="%s"><span class="label">Previous</span>%s</a>',
                htmlspecialchars($prev['file'], ENT_QUOTES | ENT_HTML5),
                htmlspecialchars($prev['title'], ENT_QUOTES | ENT_HTML5),
            );
        } else {
            $nav .= '<span></span>';
        }

        if ($next !== null) {
            $nav .= sprintf(
                '<a class="next" href="%s"><span class="label">Next</span>%s</a>',
                htmlspecialchars($next['file'], ENT_QUOTES | ENT_HTML5),
                htmlspecialchars($next['title'], ENT_QUOTES | ENT_HTML5),
            );
        }

        $nav .= '</nav>';

        $body = $page['chapter']->html.$nav;
        $documentTitle = $page['title'].' — '.$this->project->title();

        return $this->document(
            pages: $pages,
            pageTitle: $documentTitle,
            activeFile: $page['file'],
            body: $body,
            topbarTitle: $page['title'],
        );
    }

    /**
     * @param  list<array{chapter: Chapter, file: string, title: string}>  $pages
     */
    private function document(
        array $pages,
        string $pageTitle,
        string $activeFile,
        string $body,
        string $topbarTitle,
    ): string {
        $title = htmlspecialchars($pageTitle, ENT_QUOTES | ENT_HTML5);
        $bookTitle = htmlspecialchars($this->project->title(), ENT_QUOTES | ENT_HTML5);
        $bookSubtitle = htmlspecialchars($this->project->subtitle(), ENT_QUOTES | ENT_HTML5);
        $topbar = htmlspecialchars($topbarTitle, ENT_QUOTES | ENT_HTML5);
        $sidebar = $this->sidebarHtml($pages, $activeFile);

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$title}</title>
    <link rel="stylesheet" href="assets/site.css">
    <script src="assets/site.js"></script>
</head>
<body>
    <div class="sidebar-backdrop" id="sidebar-backdrop"></div>
    <aside class="sidebar" id="sidebar" aria-label="Chapters">
        <div class="sidebar-header">
            <a class="sidebar-brand" href="index.html">{$bookTitle}</a>
            <p class="sidebar-subtitle">{$bookSubtitle}</p>
        </div>
        {$sidebar}
    </aside>
    <div class="layout">
        <header class="topbar">
            <button type="button" class="nav-toggle" id="nav-toggle" aria-expanded="false" aria-controls="sidebar" aria-label="Open chapter list" title="Chapters">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
            </button>
            <p class="topbar-title">{$topbar}</p>
            <div class="topbar-actions">
                <button type="button" class="theme-toggle" id="theme-toggle" aria-pressed="false" aria-label="Switch to dark mode" title="Dark mode">
                    <svg class="icon-moon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                    </svg>
                    <svg class="icon-sun" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <circle cx="12" cy="12" r="4"/>
                        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
                    </svg>
                </button>
            </div>
        </header>
        <main class="content">
            {$body}
        </main>
    </div>
</body>
</html>
HTML;
    }

    /**
     * @param  list<array{chapter: Chapter, file: string, title: string}>  $pages
     */
    private function sidebarHtml(array $pages, string $activeFile): string
    {
        $items = '';

        $homeClass = $activeFile === 'index.html' ? ' class="is-active"' : '';
        $homeCurrent = $activeFile === 'index.html' ? ' aria-current="page"' : '';
        $items .= sprintf(
            '<li><a%s href="index.html"%s>Home</a></li>',
            $homeClass,
            $homeCurrent,
        );

        foreach ($pages as $page) {
            $isActive = $page['file'] === $activeFile;
            $items .= sprintf(
                '<li><a%s href="%s"%s>%s</a></li>',
                $isActive ? ' class="is-active"' : '',
                htmlspecialchars($page['file'], ENT_QUOTES | ENT_HTML5),
                $isActive ? ' aria-current="page"' : '',
                htmlspecialchars($page['title'], ENT_QUOTES | ENT_HTML5),
            );
        }

        return '<nav class="sidebar-nav"><ul>'.$items.'</ul></nav>';
    }

    private function copySiteBanner(string $assetsDir): void
    {
        $banner = $this->project->siteBanner();

        if ($banner === null) {
            return;
        }

        $source = $this->project->assetsDir.'/'.$banner;

        if (! is_file($source)) {
            return;
        }

        $destination = $assetsDir.'/'.basename($banner);

        if (! copy($source, $destination)) {
            throw new HtmlException(sprintf('Unable to copy site banner: %s', $banner));
        }
    }

    private function copyFontsIntoSite(string $destinationDir): void
    {
        $sourceDir = $this->project->assetsDir.'/fonts';

        if (! is_dir($sourceDir)) {
            return;
        }

        if (! is_dir($destinationDir) && ! mkdir($destinationDir, 0755, true) && ! is_dir($destinationDir)) {
            throw new HtmlException(sprintf('Unable to create site fonts directory: %s', $destinationDir));
        }

        $files = scandir($sourceDir);

        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $source = $sourceDir.'/'.$file;

            if (! is_file($source)) {
                continue;
            }

            $destination = $destinationDir.'/'.$file;

            if (! copy($source, $destination)) {
                throw new HtmlException(sprintf('Unable to copy font into site: %s', $file));
            }
        }
    }

    private function writeFile(string $path, string $contents): void
    {
        if (file_put_contents($path, $contents) === false) {
            throw new HtmlException(sprintf('Unable to write site file: %s', $path));
        }
    }
}
