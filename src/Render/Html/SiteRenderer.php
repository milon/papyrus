<?php

declare(strict_types=1);

namespace Milon\Papyrus\Render\Html;

use Milon\Papyrus\Book\Chapter;
use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Markdown\HeadingAnchors;

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
        $this->copyProjectAssets($assetsDir);
        $this->copySiteBanner($assetsDir);

        $css = WebTheme::fontFaceCss('fonts/')
            ."\n"
            .WebTheme::contentCss()
            ."\n"
            .WebTheme::siteLayoutCss();

        $this->writeFile($assetsDir.'/site.css', $css);
        $this->writeFile($assetsDir.'/site.js', WebTheme::themeScript());
        $this->writeFile($siteDir.'/.nojekyll', '');
        $this->writeCname($siteDir);

        /** @var list<array{chapter: Chapter, file: string, title: string}> $pages */
        $pages = [];

        foreach ($chapters as $chapter) {
            $pages[] = [
                'chapter' => $chapter,
                'file' => $chapter->webSlug().'.html',
                'title' => $chapter->displayTitle(),
            ];
        }

        $pages = $this->orderPagesByNav($pages);

        $this->writeFile($siteDir.'/index.html', $this->renderIndex($pages));
        $this->writeFile($siteDir.'/404.html', $this->renderNotFound($pages));

        foreach ($pages as $index => $page) {
            $prev = $pages[$index - 1] ?? null;
            $next = $pages[$index + 1] ?? null;
            $html = $this->renderChapterPage($pages, $page, $prev, $next);
            $this->writeFile($siteDir.'/'.$page['file'], $html);
        }

        $this->writeSearchIndex($assetsDir, $pages);
        $this->writeSitemap($siteDir, $pages);
        $this->writeRobots($siteDir);

        return $siteDir;
    }

    /**
     * @param  list<array{chapter: Chapter, file: string, title: string}>  $pages
     */
    private function renderIndex(array $pages): string
    {
        if ($this->project->isDocsSite()) {
            return $this->renderDocsIndex($pages);
        }

        return $this->renderBookIndex($pages);
    }

    /**
     * @param  list<array{chapter: Chapter, file: string, title: string}>  $pages
     */
    private function renderBookIndex(array $pages): string
    {
        $first = $pages[0];
        $title = htmlspecialchars($this->project->title(), ENT_QUOTES | ENT_HTML5);
        $subtitle = htmlspecialchars($this->project->subtitle(), ENT_QUOTES | ENT_HTML5);
        $author = htmlspecialchars($this->project->author(), ENT_QUOTES | ENT_HTML5);
        $firstFile = htmlspecialchars($first['file'], ENT_QUOTES | ENT_HTML5);
        $firstTitle = htmlspecialchars($first['title'], ENT_QUOTES | ENT_HTML5);

        $bannerHtml = $this->homeBannerHtml($title);
        $leadHtml = $this->homeLeadHtml();
        $linksHtml = $this->homeLinksHtml($pages);

        $subtitleHtml = $this->project->subtitle() !== ''
            ? '<p class="book-subtitle">'.$subtitle.'</p>'
            : '';
        $authorHtml = $this->project->author() !== ''
            ? '<p class="book-author">'.$author.'</p>'
            : '';

        $body = <<<HTML
<div class="title-page">
    {$bannerHtml}
    <h1 class="book-title">{$title}</h1>
    {$subtitleHtml}
    {$leadHtml}
    {$authorHtml}
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
    private function renderDocsIndex(array $pages): string
    {
        $cta = $this->docsStartPage($pages);
        $title = htmlspecialchars($this->project->title(), ENT_QUOTES | ENT_HTML5);
        $ctaFile = htmlspecialchars($cta['file'], ENT_QUOTES | ENT_HTML5);
        $ctaTitle = htmlspecialchars($cta['title'], ENT_QUOTES | ENT_HTML5);

        $bannerHtml = $this->homeBannerHtml($title);
        $leadHtml = $this->homeLeadHtml();

        $subtitleHtml = '';
        if ($this->project->subtitle() !== '') {
            $subtitleHtml = '<p class="book-subtitle">'.htmlspecialchars($this->project->subtitle(), ENT_QUOTES | ENT_HTML5).'</p>';
        }

        $linksHtml = $this->homeLinksHtml($pages, 'docs-home-links');

        $body = <<<HTML
<div class="title-page docs-home">
    {$bannerHtml}
    <p class="docs-kicker">Documentation</p>
    <h1 class="book-title">{$title}</h1>
    {$subtitleHtml}
    {$leadHtml}
    <p class="docs-actions">
        <a class="docs-cta-primary" href="{$ctaFile}">Get started — {$ctaTitle}</a>
    </p>
    {$linksHtml}
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

    private function homeBannerHtml(string $escapedTitle): string
    {
        $banner = $this->project->siteBanner();

        if ($banner === null || ! is_file($this->project->assetsDir.'/'.$banner)) {
            return '';
        }

        return sprintf(
            '<p class="book-banner"><img src="assets/%s" alt="%s"/></p>',
            htmlspecialchars($banner, ENT_QUOTES | ENT_HTML5),
            $escapedTitle,
        );
    }

    private function homeLeadHtml(): string
    {
        $lead = $this->project->siteLead();

        if ($lead === null) {
            return '';
        }

        return '<p class="book-lead">'.htmlspecialchars($lead, ENT_QUOTES | ENT_HTML5).'</p>';
    }

    /**
     * Prefer Install / Getting started chapters for the docs CTA.
     *
     * @param  list<array{chapter: Chapter, file: string, title: string}>  $pages
     * @return array{chapter: Chapter, file: string, title: string}
     */
    private function docsStartPage(array $pages): array
    {
        foreach ($pages as $page) {
            $slug = strtolower($page['chapter']->webSlug());
            $title = strtolower($page['title']);

            if (
                str_contains($slug, 'install')
                || str_contains($title, 'install')
                || str_contains($slug, 'quick-start')
                || str_contains($title, 'quick start')
                || str_contains($slug, 'getting-started')
                || str_contains($title, 'getting started')
            ) {
                return $page;
            }
        }

        foreach ($pages as $page) {
            $slug = strtolower($page['chapter']->webSlug());
            $title = strtolower($page['title']);

            if (
                str_contains($slug, 'welcome')
                || $title === 'welcome'
                || str_starts_with($slug, '00-')
            ) {
                continue;
            }

            return $page;
        }

        return $pages[0];
    }

    /**
     * @param  list<array{chapter: Chapter, file: string, title: string}>  $pages
     */
    private function homeLinksHtml(array $pages, string $class = 'home-links'): string
    {
        $items = '';
        $links = $this->project->siteLinks();

        foreach ($links as $link) {
            $href = $link['url'] ?? $this->chapterHref($pages, $link['chapter'] ?? null);

            if ($href === null) {
                continue;
            }

            $items .= sprintf(
                '<a href="%s"%s>%s</a>',
                htmlspecialchars($href, ENT_QUOTES | ENT_HTML5),
                $this->isExternalUrl($href) ? ' rel="noopener noreferrer"' : '',
                htmlspecialchars($link['label'], ENT_QUOTES | ENT_HTML5),
            );
        }

        if ($items === '') {
            return '';
        }

        return '<p class="'.htmlspecialchars($class, ENT_QUOTES | ENT_HTML5).'">'.$items.'</p>';
    }

    private function isExternalUrl(string $href): bool
    {
        return preg_match('#^https?://#i', $href) === 1;
    }

    /**
     * @param  list<array{chapter: Chapter, file: string, title: string}>  $pages
     */
    private function renderNotFound(array $pages): string
    {
        $title = htmlspecialchars($this->project->title(), ENT_QUOTES | ENT_HTML5);
        $body = <<<HTML
<div class="not-found">
    <p class="not-found-code">404</p>
    <h1>Page not found</h1>
    <p>This URL is not part of {$title}.</p>
    <a class="start-reading" href="index.html">Back to home</a>
</div>
HTML;

        return $this->document(
            pages: $pages,
            pageTitle: 'Page not found — '.$this->project->title(),
            activeFile: '404.html',
            body: $body,
            topbarTitle: 'Page not found',
            extraHead: '<meta name="robots" content="noindex">',
        );
    }

    /**
     * @param  list<array{chapter: Chapter, file: string, title: string}>  $pages
     */
    private function chapterHref(array $pages, ?string $chapterName): ?string
    {
        if ($chapterName === null) {
            return null;
        }

        foreach ($pages as $page) {
            if (! $this->pageMatchesChapterName($page, $chapterName)) {
                continue;
            }

            return $page['file'];
        }

        return null;
    }

    /**
     * @param  array{chapter: Chapter, file: string, title: string}  $page
     */
    private function pageMatchesChapterName(array $page, string $chapterName): bool
    {
        $name = strtolower(trim($chapterName));

        if ($name === '') {
            return false;
        }

        foreach ($this->chapterAliases($page['chapter']) as $alias) {
            if ($alias === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function chapterAliases(Chapter $chapter): array
    {
        $source = strtolower(ltrim(str_replace('\\', '/', $chapter->source), '/'));
        $base = basename($source);
        $stem = pathinfo($base, PATHINFO_FILENAME);

        return array_values(array_unique(array_filter([
            $source,
            $base,
            $stem,
            $stem.'.md',
        ])));
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

        [$anchoredHtml, $headings] = HeadingAnchors::process($page['chapter']->html);
        $edit = $this->editPageHtml($page['chapter']->source);
        $main = $anchoredHtml.$edit.$nav;
        $toc = $this->pageTocHtml($headings, $page['title']);
        $body = $toc === ''
            ? $main
            : '<div class="content-with-toc"><div class="content-main">'.$main.'</div>'.$toc.'</div>';
        $documentTitle = $page['title'].' — '.$this->project->title();

        return $this->document(
            pages: $pages,
            pageTitle: $documentTitle,
            activeFile: $page['file'],
            body: $body,
            topbarTitle: $page['title'],
        );
    }

    private function editPageHtml(string $chapterSource): string
    {
        $url = $this->project->chapterEditUrl($chapterSource);

        if ($url === null) {
            return '';
        }

        $icon = '<svg class="edit-page-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">'
            .'<path d="M12 20h9" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>'
            .'<path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>'
            .'</svg>';

        return sprintf(
            '<p class="edit-page"><a href="%s" target="_blank" rel="noopener noreferrer">%s<span>Edit this page</span></a></p>',
            htmlspecialchars($url, ENT_QUOTES | ENT_HTML5),
            $icon,
        );
    }

    /**
     * @param  list<array{id: string, title: string, level: int}>  $headings
     */
    private function pageTocHtml(array $headings, string $pageTitle): string
    {
        if (! $this->project->sitePageTocEnabled()) {
            return '';
        }

        if ($headings === []) {
            $items = sprintf(
                '<li><a href="#">%s</a></li>',
                htmlspecialchars($pageTitle, ENT_QUOTES | ENT_HTML5),
            );
        } else {
            $items = '';
            $count = count($headings);

            for ($i = 0; $i < $count; $i++) {
                $heading = $headings[$i];

                if ($heading['level'] > 2) {
                    // Orphan h3 (no preceding h2) — treat as a top-level link.
                    $items .= $this->pageTocItem($heading);

                    continue;
                }

                $children = '';

                while ($i + 1 < $count && $headings[$i + 1]['level'] > 2) {
                    $i++;
                    $children .= $this->pageTocItem($headings[$i]);
                }

                if ($children === '') {
                    $items .= $this->pageTocItem($heading);
                } else {
                    $items .= sprintf(
                        '<li><a href="#%s">%s</a><ul>%s</ul></li>',
                        htmlspecialchars($heading['id'], ENT_QUOTES | ENT_HTML5),
                        htmlspecialchars($heading['title'], ENT_QUOTES | ENT_HTML5),
                        $children,
                    );
                }
            }
        }

        return sprintf(
            '<aside class="page-toc" aria-label="On this page">'
            .'<p class="page-toc-title">'
            .'<svg class="page-toc-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true" focusable="false">'
            .'<path d="M1.833 8h12.333M1.833 3.833h12.333M1.833 12.167H7.666" stroke="currentColor" stroke-width="1.25" stroke-linecap="square"/>'
            .'</svg>'
            .'On this page'
            .'</p>'
            .'<div class="page-toc-rail"><ul>%s</ul></div>'
            .'</aside>',
            $items,
        );
    }

    /**
     * @param  array{id: string, title: string, level: int}  $heading
     */
    private function pageTocItem(array $heading): string
    {
        return sprintf(
            '<li><a href="#%s">%s</a></li>',
            htmlspecialchars($heading['id'], ENT_QUOTES | ENT_HTML5),
            htmlspecialchars($heading['title'], ENT_QUOTES | ENT_HTML5),
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
        string $extraHead = '',
    ): string {
        $title = htmlspecialchars($pageTitle, ENT_QUOTES | ENT_HTML5);
        $bookTitle = htmlspecialchars($this->project->title(), ENT_QUOTES | ENT_HTML5);
        $bookSubtitle = htmlspecialchars($this->project->subtitle(), ENT_QUOTES | ENT_HTML5);
        $topbar = htmlspecialchars($topbarTitle, ENT_QUOTES | ENT_HTML5);
        $sidebar = $this->sidebarHtml($pages, $activeFile);
        $versionSwitcher = $this->versionSwitcherHtml();
        $headExtra = $extraHead !== '' ? "\n    ".$extraHead : '';
        $baseHref = $this->baseHrefTag();
        $bodyAttrs = $this->bodyAttrs();

        return str_replace(
            [
                '{{pageTitle}}',
                '{{baseHref}}',
                '{{headExtra}}',
                '{{bookTitle}}',
                '{{bookSubtitle}}',
                '{{versionSwitcher}}',
                '{{sidebar}}',
                '{{topbarTitle}}',
                '{{bodyAttrs}}',
                '{{body}}',
            ],
            [
                $title,
                $baseHref,
                $headExtra,
                $bookTitle,
                $bookSubtitle,
                $versionSwitcher,
                $sidebar,
                $topbar,
                $bodyAttrs,
                $body,
            ],
            WebTheme::documentHtml(),
        );
    }

    private function bodyAttrs(): string
    {
        $attrs = '';

        if (! $this->project->siteCopyCodeEnabled()) {
            $attrs .= ' data-no-copy-code';
        }

        return $attrs;
    }

    private function versionSwitcherHtml(): string
    {
        $versions = $this->project->siteVersions();

        if (count($versions) < 2) {
            return '';
        }

        $options = '';

        foreach ($versions as $version) {
            $options .= sprintf(
                '<option value="%s"%s>%s</option>',
                htmlspecialchars($version['href'], ENT_QUOTES | ENT_HTML5),
                $version['current'] ? ' selected' : '',
                htmlspecialchars($version['label'], ENT_QUOTES | ENT_HTML5),
            );
        }

        return '<div class="version-switcher-wrap">'
            .'<label class="visually-hidden" for="version-switcher">Documentation version</label>'
            .'<select class="version-switcher" id="version-switcher" data-version-switcher>'
            .$options
            .'</select>'
            .'</div>';
    }

    private function baseHrefTag(): string
    {
        $basePath = $this->project->siteBasePath();

        if ($basePath === '') {
            return '';
        }

        return "\n    <base href=\"".htmlspecialchars($basePath.'/', ENT_QUOTES | ENT_HTML5).'">';
    }

    /**
     * @param  list<array{chapter: Chapter, file: string, title: string}>  $pages
     */
    private function sidebarHtml(array $pages, string $activeFile): string
    {
        $homeClass = $activeFile === 'index.html' ? ' class="is-active"' : '';
        $homeCurrent = $activeFile === 'index.html' ? ' aria-current="page"' : '';
        $home = sprintf(
            '<li><a%s href="index.html"%s>Home</a></li>',
            $homeClass,
            $homeCurrent,
        );

        $nav = $this->project->siteNav();

        if ($nav === []) {
            $items = $home;

            foreach ($pages as $page) {
                $items .= $this->sidebarPageItem($page, $activeFile);
            }

            return '<nav class="sidebar-nav"><ul>'.$items.'</ul></nav>';
        }

        $sectionsHtml = $home;
        $used = [];

        foreach ($nav as $section) {
            $sectionItems = '';

            foreach ($section['chapters'] as $chapterName) {
                $page = $this->findPageByChapterName($pages, $chapterName);

                if ($page === null) {
                    continue;
                }

                $used[$page['file']] = true;
                $sectionItems .= $this->sidebarPageItem($page, $activeFile);
            }

            if ($sectionItems === '') {
                continue;
            }

            if ($section['group'] !== null) {
                $sectionsHtml .= sprintf(
                    '<li class="sidebar-group"><span class="sidebar-group-label">%s</span><ul>%s</ul></li>',
                    htmlspecialchars($section['group'], ENT_QUOTES | ENT_HTML5),
                    $sectionItems,
                );
            } else {
                $sectionsHtml .= $sectionItems;
            }
        }

        $extra = '';

        foreach ($pages as $page) {
            if (isset($used[$page['file']])) {
                continue;
            }

            $extra .= $this->sidebarPageItem($page, $activeFile);
        }

        if ($extra !== '') {
            $sectionsHtml .= sprintf(
                '<li class="sidebar-group"><span class="sidebar-group-label">More</span><ul>%s</ul></li>',
                $extra,
            );
        }

        return '<nav class="sidebar-nav"><ul>'.$sectionsHtml.'</ul></nav>';
    }

    /**
     * @param  array{chapter: Chapter, file: string, title: string}  $page
     */
    private function sidebarPageItem(array $page, string $activeFile): string
    {
        $isActive = $page['file'] === $activeFile;

        return sprintf(
            '<li><a%s href="%s"%s>%s</a></li>',
            $isActive ? ' class="is-active"' : '',
            htmlspecialchars($page['file'], ENT_QUOTES | ENT_HTML5),
            $isActive ? ' aria-current="page"' : '',
            htmlspecialchars($page['title'], ENT_QUOTES | ENT_HTML5),
        );
    }

    /**
     * @param  list<array{chapter: Chapter, file: string, title: string}>  $pages
     * @return list<array{chapter: Chapter, file: string, title: string}>
     */
    private function orderPagesByNav(array $pages): array
    {
        $nav = $this->project->siteNav();

        if ($nav === []) {
            return $pages;
        }

        $ordered = [];
        $used = [];

        foreach ($nav as $section) {
            foreach ($section['chapters'] as $chapterName) {
                $page = $this->findPageByChapterName($pages, $chapterName);

                if ($page === null || isset($used[$page['file']])) {
                    continue;
                }

                $ordered[] = $page;
                $used[$page['file']] = true;
            }
        }

        foreach ($pages as $page) {
            if (isset($used[$page['file']])) {
                continue;
            }

            $ordered[] = $page;
        }

        return $ordered;
    }

    /**
     * @param  list<array{chapter: Chapter, file: string, title: string}>  $pages
     * @return array{chapter: Chapter, file: string, title: string}|null
     */
    private function findPageByChapterName(array $pages, string $chapterName): ?array
    {
        foreach ($pages as $page) {
            if ($this->pageMatchesChapterName($page, $chapterName)) {
                return $page;
            }
        }

        return null;
    }

    private function writeCname(string $siteDir): void
    {
        $cnamePath = $siteDir.'/CNAME';
        $cname = $this->project->siteCname();

        if ($cname === null) {
            if (is_file($cnamePath)) {
                unlink($cnamePath);
            }

            return;
        }

        $this->writeFile($cnamePath, $cname."\n");
    }

    /**
     * @param  list<array{chapter: Chapter, file: string, title: string}>  $pages
     */
    private function writeSearchIndex(string $assetsDir, array $pages): void
    {
        $entries = [
            [
                'file' => 'index.html',
                'title' => $this->project->title(),
                'text' => $this->plainText($this->project->title().' '.$this->project->subtitle().' '.($this->project->siteLead() ?? '')),
                'kind' => 'home',
                'pretoc' => false,
            ],
        ];

        foreach ($pages as $page) {
            [$anchoredHtml, $headings] = HeadingAnchors::process($page['chapter']->html);

            $entries[] = [
                'file' => $page['file'],
                'title' => $page['title'],
                'text' => $this->plainText($page['title'].' '.$anchoredHtml),
                'kind' => 'chapter',
                'pretoc' => $page['chapter']->pretoc,
            ];

            foreach ($headings as $heading) {
                $entries[] = [
                    'file' => $page['file'].'#'.$heading['id'],
                    'title' => $heading['title'],
                    'text' => $this->plainText($heading['title'].' '.$page['title']),
                    'kind' => 'heading',
                    'pretoc' => $page['chapter']->pretoc,
                ];
            }
        }

        $json = json_encode($entries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new HtmlException('Unable to encode site search index.');
        }

        $this->writeFile($assetsDir.'/search.json', $json."\n");
    }

    /**
     * @param  list<array{chapter: Chapter, file: string, title: string}>  $pages
     */
    private function writeSitemap(string $siteDir, array $pages): void
    {
        $files = ['index.html'];

        foreach ($pages as $page) {
            $files[] = $page['file'];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($files as $file) {
            $loc = htmlspecialchars($this->absolutePageUrl($file), ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $xml .= "  <url><loc>{$loc}</loc></url>\n";
        }

        $xml .= '</urlset>'."\n";

        $this->writeFile($siteDir.'/sitemap.xml', $xml);
    }

    private function writeRobots(string $siteDir): void
    {
        $body = "User-agent: *\nAllow: /\n";
        $cname = $this->project->siteCname();

        if ($cname !== null) {
            $body .= 'Sitemap: '.$this->absolutePageUrl('sitemap.xml')."\n";
        }

        $this->writeFile($siteDir.'/robots.txt', $body);
    }

    private function absolutePageUrl(string $file): string
    {
        $path = $this->project->siteBasePath().'/'.ltrim($file, '/');
        $cname = $this->project->siteCname();

        if ($cname !== null) {
            return 'https://'.$cname.$path;
        }

        return $path;
    }

    private function plainText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = trim($text);

        if (strlen($text) > 8000) {
            return substr($text, 0, 8000);
        }

        return $text;
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

    /**
     * Copy project assets/ into the site (banners, example images, …), excluding fonts/.
     */
    private function copyProjectAssets(string $assetsDir): void
    {
        $source = $this->project->assetsDir;

        if (! is_dir($source)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            $relative = substr($file->getPathname(), strlen(rtrim($source, DIRECTORY_SEPARATOR)) + 1);
            $relative = str_replace('\\', '/', $relative);

            if ($relative === 'fonts' || str_starts_with($relative, 'fonts/')) {
                continue;
            }

            $destination = $assetsDir.'/'.$relative;

            if ($file->isDir()) {
                if (! is_dir($destination) && ! mkdir($destination, 0755, true) && ! is_dir($destination)) {
                    throw new HtmlException(sprintf('Unable to create site asset directory: %s', $destination));
                }

                continue;
            }

            $parent = dirname($destination);

            if (! is_dir($parent) && ! mkdir($parent, 0755, true) && ! is_dir($parent)) {
                throw new HtmlException(sprintf('Unable to create site asset directory: %s', $parent));
            }

            if (! copy($file->getPathname(), $destination)) {
                throw new HtmlException(sprintf('Unable to copy site asset: %s', $relative));
            }
        }
    }

    private function copyFontsIntoSite(string $destinationDir): void
    {
        $sourceDirs = $this->project->fontDirs();

        if ($sourceDirs === []) {
            return;
        }

        if (! is_dir($destinationDir) && ! mkdir($destinationDir, 0755, true) && ! is_dir($destinationDir)) {
            throw new HtmlException(sprintf('Unable to create site fonts directory: %s', $destinationDir));
        }

        foreach (array_reverse($sourceDirs) as $sourceDir) {
            $files = scandir($sourceDir);

            if ($files === false) {
                continue;
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
    }

    private function writeFile(string $path, string $contents): void
    {
        if (file_put_contents($path, $contents) === false) {
            throw new HtmlException(sprintf('Unable to write site file: %s', $path));
        }
    }
}
