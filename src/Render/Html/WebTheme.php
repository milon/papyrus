<?php

declare(strict_types=1);

namespace Milon\Papyrus\Render\Html;

/**
 * Shared light/dark palette and prose styles for single-file HTML and sites.
 */
final class WebTheme
{
    public static function contentCss(): string
    {
        return self::read('content.css');
    }

    public static function siteLayoutCss(): string
    {
        return self::read('layout.css').self::readingColumnMaxWidthCss('.content');
    }

    /**
     * Tailwind default screens as a centered container max-width ladder
     * (sm 640 / md 768 / lg 1024 / xl 1280 / 2xl 1536).
     *
     * @see https://tailwindcss.com/docs/responsive-design
     */
    public static function readingColumnMaxWidthCss(string $selector): string
    {
        return str_replace('{{selector}}', $selector, self::read('reading-column.css'));
    }

    public static function fontFaceCss(string $fontsUrlPrefix): string
    {
        $prefix = rtrim($fontsUrlPrefix, '/').'/';

        return str_replace('{{prefix}}', $prefix, self::read('fonts.css'));
    }

    public static function themeScript(): string
    {
        return self::read('site.js');
    }

    public static function documentHtml(): string
    {
        return self::read('document.html');
    }

    private static function read(string $file): string
    {
        $path = __DIR__.'/theme/'.$file;
        $contents = is_file($path) ? file_get_contents($path) : false;

        if ($contents === false) {
            throw new HtmlException(sprintf('Unable to read web theme file: %s', $path));
        }

        return $contents;
    }
}
