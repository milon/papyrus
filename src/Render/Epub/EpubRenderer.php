<?php

declare(strict_types=1);

namespace Milon\Papyrus\Render\Epub;

use Milon\Papyrus\Book\Chapter;
use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Render\VendorNotices;
use PHPePub\Core\EPub;

final class EpubRenderer
{
    public function __construct(
        private readonly Project $project,
    ) {}

    public function render(?string $outputPath = null, ?EpubOptions $options = null): string
    {
        return VendorNotices::silence(
            fn (): string => $this->write($outputPath, $options),
        );
    }

    private function write(?string $outputPath, ?EpubOptions $options): string
    {
        $options ??= new EpubOptions;

        $styleCss = $this->readAsset('style.css');
        $codeblockCss = $this->readAsset('highlight.codeblock.min.css');

        $book = $this->project->bookWithFigures(breakLevel: 1, exportTheme: 'default');
        $language = $this->project->language();

        $contentStart = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops">'."\n"
            .'<head>'
            .'<meta http-equiv="Default-Style" content="text/html; charset=utf-8" />'."\n"
            .'<link rel="stylesheet" type="text/css" href="codeblock.css" />'."\n"
            .'<link rel="stylesheet" type="text/css" href="style.css" />'."\n"
            .'<title>'.htmlspecialchars($this->project->title(), ENT_QUOTES | ENT_HTML5)."</title>\n"
            ."</head>\n"
            ."<body>\n";
        $contentEnd = '</body></html>';

        $epub = new EPub(EPub::BOOK_VERSION_EPUB3, $language, EPub::DIRECTION_LEFT_TO_RIGHT);
        $epub->setIdentifier(
            md5(sprintf('%s - %s', $this->project->title(), $this->project->author())),
            EPub::IDENTIFIER_UUID,
        );
        $epub->setLanguage($language);
        $epub->setDescription($options->description ?? sprintf('%s - %s', $this->project->title(), $this->project->author()));
        $epub->setTitle($this->project->title());
        $epub->setAuthor($this->project->author(), $this->project->author());
        $epub->setIdentifier(
            $this->project->title().'&amp;stamp='.time(),
            EPub::IDENTIFIER_URI,
        );

        $epub->addCSSFile('style.css', 'css1', $styleCss);
        $epub->addCSSFile('codeblock.css', 'css2', $codeblockCss);

        $coverHtml = $contentStart.'<h1>'.htmlspecialchars($this->project->title(), ENT_QUOTES | ENT_HTML5)."</h1>\n";

        if ($this->project->author() !== '') {
            $coverHtml .= '<h2>By: '.htmlspecialchars($this->project->author(), ENT_QUOTES | ENT_HTML5)."</h2>\n";
        }

        $coverHtml .= $contentEnd;

        $this->addCoverImage($epub, $options->coverImageName);

        $epub->addChapter('Cover', 'Cover.html', $coverHtml);
        $epub->addChapter('Table of Contents', 'TOC.xhtml', null, false, EPub::EXTERNAL_REF_IGNORE);

        foreach ($book->chapters as $index => $chapter) {
            $chapterHtml = str_replace('</span> <span', '</span>&nbsp;<span', $chapter->html);
            $title = $chapter->title() !== '' ? $chapter->title() : 'Chapter '.($index + 1);

            $epub->addChapter(
                chapterName: $title,
                fileName: 'Chapter'.$index.'.html',
                chapterData: $contentStart.$chapterHtml.$contentEnd,
                externalReferences: EPub::EXTERNAL_REF_ADD,
            );

            $this->addChapterImages($epub, $chapter, $index);
        }

        $epub->buildTOC(title: 'Index', addReferences: false);
        $epub->finalize();

        if (! is_dir($this->project->exportDir)) {
            mkdir($this->project->exportDir, 0755, true);
        }

        $filename = $outputPath ?? sprintf(
            '%s/%s.epub',
            $this->project->exportDir,
            $this->project->outputSlug(),
        );

        $result = $epub->saveBook(basename($filename), dirname($filename));

        if ($result === false) {
            throw new EpubException(sprintf('Unable to write EPUB file: %s', $filename));
        }

        return $filename;
    }

    private function readAsset(string $filename): string
    {
        $path = $this->project->assetPath($filename);

        if ($path === null) {
            throw new EpubException(sprintf('EPUB asset not found: %s', $filename));
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new EpubException(sprintf('Unable to read EPUB asset: %s', $path));
        }

        return $contents;
    }

    private function addCoverImage(EPub $epub, ?string $coverImageName = null): void
    {
        $imageName = $coverImageName
            ?? $this->project->coverImageForTheme('light')
            ?? $this->project->coverImageForTheme('dark');

        if ($imageName === null) {
            return;
        }

        $path = $this->project->assetsDir.'/'.$imageName;

        if (! is_file($path)) {
            return;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return;
        }

        $mime = mime_content_type($path) ?: 'image/jpeg';
        $epub->setCoverImage('cover.jpg', $contents, $mime);
    }

    private function addChapterImages(EPub $epub, Chapter $chapter, int $chapterIndex): void
    {
        foreach ($chapter->imageReferences() as $imageIndex => $reference) {
            if (filter_var($reference, FILTER_VALIDATE_URL)) {
                continue;
            }

            $path = $this->resolveImagePath($chapter, $reference);

            if ($path === null) {
                continue;
            }

            $mime = mime_content_type($path) ?: 'application/octet-stream';

            $epub->addLargeFile(
                $reference,
                'image-'.$chapterIndex.'-'.$imageIndex,
                $path,
                $mime,
            );
        }
    }

    private function resolveImagePath(Chapter $chapter, string $reference): ?string
    {
        if ($reference === '') {
            return null;
        }

        if ($reference[0] === '/') {
            return is_file($reference) ? $reference : null;
        }

        $chapterRelative = $chapter->path;
        $candidate = dirname($chapterRelative).'/'.$reference;

        if (is_file($candidate)) {
            return $candidate;
        }

        $contentRelative = $this->project->contentDir.'/'.$reference;

        return is_file($contentRelative) ? $contentRelative : null;
    }
}
