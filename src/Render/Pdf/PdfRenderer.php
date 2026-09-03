<?php

declare(strict_types=1);

namespace Milon\Papyrus\Render\Pdf;

use Milon\Papyrus\Book\Book;
use Milon\Papyrus\Book\Chapter;
use Milon\Papyrus\Config\DocumentSize;
use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Render\VendorNotices;
use Milon\Papyrus\Theme\Theme;
use Milon\Papyrus\Theme\ThemeException;
use Mpdf\Mpdf;
use Mpdf\MpdfException;

final class PdfRenderer
{
    public function __construct(
        private readonly Project $project,
    ) {}

    public function render(
        string $themeName,
        ?string $outputPath = null,
        ?DocumentSize $documentSize = null,
        bool $skipCover = false,
        ?Book $book = null,
        bool $bodyOnly = false,
    ): string {
        return VendorNotices::silence(
            fn (): string => $this->write($themeName, $outputPath, $documentSize, $skipCover, $book, $bodyOnly),
        );
    }

    private function write(
        string $themeName,
        ?string $outputPath,
        ?DocumentSize $documentSize,
        bool $skipCover,
        ?Book $book,
        bool $bodyOnly,
    ): string {
        $themePath = $this->project->assetsDir.'/theme-'.$themeName.'.html';

        try {
            $theme = Theme::load($themePath, $this->themeReplacements());
        } catch (ThemeException $e) {
            throw new PdfException($e->getMessage(), previous: $e);
        }

        $book ??= $this->project->bookWithFigures(breakLevel: null, exportTheme: $themeName);
        $document = $documentSize ?? $this->project->documentSize();

        $pdf = MpdfFactory::create($this->project, $document);

        $pdf->SetTitle($this->project->title());
        $pdf->SetAuthor($this->project->author());
        $pdf->SetCreator($this->project->author());

        $basePath = realpath($this->project->contentDir);

        if ($basePath !== false) {
            $pdf->SetBasePath($basePath);
        }

        $pdf->setAutoTopMargin = 'pad';
        $pdf->setAutoBottomMargin = 'pad';

        $tocLevels = $this->project->tocLevels();
        $pdf->h2toc = $tocLevels;
        $pdf->h2bookmarks = $tocLevels;

        $headerStyle = htmlspecialchars($this->project->headerStyle(), ENT_QUOTES | ENT_HTML5);

        if ($bodyOnly) {
            $this->writeBodyOnly($pdf, $theme, $book, $headerStyle);
        } else {
            $this->suppressFolios($pdf);

            if (! $skipCover) {
                $this->writeCover($pdf, $themeName, $document->widthMm, $document->heightMm);
            }

            try {
                $pdf->WriteHTML($theme->head);
            } catch (MpdfException $e) {
                throw new PdfException($e->getMessage(), previous: $e);
            }

            foreach ($book->preToc() as $chapter) {
                $this->writeChapter($pdf, $chapter, $headerStyle, frontMatter: true);
            }

            try {
                $pdf->WriteHTML($theme->tail);
            } catch (MpdfException $e) {
                throw new PdfException($e->getMessage(), previous: $e);
            }

            $this->enableFolios($pdf);

            foreach ($book->body() as $chapter) {
                $this->writeChapter($pdf, $chapter, $headerStyle, frontMatter: false);
            }
        }

        $pdf->SetHTMLHeader(sprintf(
            '<div style="%s">%s</div>',
            $headerStyle,
            htmlspecialchars($this->project->title(), ENT_QUOTES | ENT_HTML5),
        ));

        if (! is_dir($this->project->exportDir)) {
            mkdir($this->project->exportDir, 0755, true);
        }

        $filename = $outputPath ?? sprintf(
            '%s/%s-%s.pdf',
            $this->project->exportDir,
            $this->project->outputSlug(),
            $themeName,
        );

        try {
            $pdf->Output($filename);
        } catch (MpdfException $e) {
            throw new PdfException($e->getMessage(), previous: $e);
        }

        return $filename;
    }

    /**
     * Sample / excerpt mode: theme CSS only — no cover, title page, or TOC.
     */
    private function writeBodyOnly(Mpdf $pdf, Theme $theme, Book $book, string $headerStyle): void
    {
        $styles = $this->themeStyles($theme->head);

        try {
            $pdf->WriteHTML($styles !== '' ? $styles : $theme->head);
        } catch (MpdfException $e) {
            throw new PdfException($e->getMessage(), previous: $e);
        }

        $this->enableFolios($pdf);

        foreach ($book->chapters as $index => $chapter) {
            $html = $index === 0
                ? $this->stripLeadingPageBreak($chapter->html)
                : $chapter->html;

            $this->writeChapterHtml($pdf, $chapter, $html, $headerStyle, frontMatter: false);
        }
    }

    private function stripLeadingPageBreak(string $html): string
    {
        return (string) preg_replace(
            '/^(?:\s*<div style="page-break-after:\s*always;?"><\/div>)+/i',
            '',
            $html,
            1,
        );
    }

    private function themeStyles(string $head): string
    {
        if (preg_match('/<header\b[^>]*>.*?<\/header>/is', $head, $matches) === 1) {
            return $matches[0];
        }

        return '';
    }

    private function suppressFolios(Mpdf $pdf): void
    {
        $pdf->SetHTMLFooter('');
        $pdf->SetHTMLHeader('');
    }

    private function enableFolios(Mpdf $pdf): void
    {
        $pdf->SetHTMLFooter('<div style="text-align: center">{PAGENO}</div>');
    }

    private function writeCover(Mpdf $pdf, string $themeName, float $pageWidthMm, float $pageHeightMm): void
    {
        $imageName = $this->project->coverImageForTheme($themeName);

        if ($imageName === null) {
            return;
        }

        $path = $this->project->assetsDir.'/'.$imageName;

        if (! is_file($path)) {
            return;
        }

        $cover = $this->project->config['cover'] ?? [];
        $cover = is_array($cover) ? $cover : [];

        $width = isset($cover['width']) ? (float) $cover['width'] : $pageWidthMm;
        $height = isset($cover['height']) ? (float) $cover['height'] : $pageHeightMm;

        $absolutePath = realpath($path);

        if ($absolutePath === false) {
            return;
        }

        try {
            $pdf->WriteHTML(sprintf(
                '<div style="position: absolute; left: 0; right: 0; top: 0; bottom: 0;"><img src="%s" style="width: %smm; height: %smm; margin: 0"/></div>',
                htmlspecialchars($absolutePath, ENT_QUOTES | ENT_HTML5),
                $width,
                $height,
            ));
            $pdf->AddPage();
        } catch (MpdfException $e) {
            throw new PdfException($e->getMessage(), previous: $e);
        }
    }

    private function writeChapter(Mpdf $pdf, Chapter $chapter, string $headerStyle, bool $frontMatter): void
    {
        $this->writeChapterHtml($pdf, $chapter, $chapter->html, $headerStyle, $frontMatter);
    }

    private function writeChapterHtml(
        Mpdf $pdf,
        Chapter $chapter,
        string $html,
        string $headerStyle,
        bool $frontMatter,
    ): void {
        if (! $frontMatter) {
            $title = $chapter->title() !== '' ? $chapter->title() : $this->project->title();

            $pdf->SetHTMLHeader(sprintf(
                '<div style="%s">%s</div>',
                $headerStyle,
                htmlspecialchars($title, ENT_QUOTES | ENT_HTML5),
            ));
        }

        try {
            $pdf->WriteHTML($html);
        } catch (MpdfException $e) {
            throw new PdfException($e->getMessage(), previous: $e);
        }
    }

    /**
     * @return array<string, string>
     */
    private function themeReplacements(): array
    {
        return [
            '{{$title}}' => htmlspecialchars($this->project->title(), ENT_QUOTES | ENT_HTML5),
            '{{$subtitle}}' => htmlspecialchars($this->project->subtitle(), ENT_QUOTES | ENT_HTML5),
            '{{$author}}' => htmlspecialchars($this->project->author(), ENT_QUOTES | ENT_HTML5),
        ];
    }
}
