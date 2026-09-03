<?php

declare(strict_types=1);

namespace Milon\Papyrus\Markdown;

/**
 * Adds fragment ids and "#" permalinks to headings for HTML / site builds.
 */
final class HeadingAnchors
{
    /**
     * @return array{0: string, 1: list<array{id: string, title: string}>}
     */
    public static function process(string $html, int $minLevel = 2, int $maxLevel = 2): array
    {
        $used = [];
        $headings = [];

        $result = preg_replace_callback(
            '/<h([1-6])(\s[^>]*)?>(.*?)<\/h\1>/is',
            function (array $matches) use ($minLevel, $maxLevel, &$used, &$headings): string {
                $level = (int) $matches[1];

                if ($level < $minLevel || $level > $maxLevel) {
                    return $matches[0];
                }

                $attrs = $matches[2] ?? '';
                $inner = $matches[3];
                $title = self::plainText(self::withoutPermalink($inner));

                if ($title === '') {
                    return $matches[0];
                }

                $id = self::existingId($attrs) ?? self::uniqueSlug($title, $used);
                $used[$id] = true;
                $headings[] = ['id' => $id, 'title' => $title];

                if (self::existingId($attrs) === null) {
                    $attrs = rtrim($attrs).' id="'.htmlspecialchars($id, ENT_QUOTES | ENT_HTML5).'"';
                }

                if (preg_match('/class=["\'][^"\']*\bheading-permalink\b/', $inner) === 1) {
                    return '<h'.$level.$attrs.'>'.$inner.'</h'.$level.'>';
                }

                $permalink = sprintf(
                    '<a class="heading-permalink" href="#%s" aria-hidden="true" tabindex="-1">#</a>',
                    htmlspecialchars($id, ENT_QUOTES | ENT_HTML5),
                );

                return '<h'.$level.$attrs.'>'.$permalink.$inner.'</h'.$level.'>';
            },
            $html,
        );

        return [is_string($result) ? $result : $html, $headings];
    }

    public static function decorate(string $html, int $minLevel = 2, int $maxLevel = 2): string
    {
        return self::process($html, $minLevel, $maxLevel)[0];
    }

    private static function existingId(string $attrs): ?string
    {
        if (preg_match('/\bid=["\']([^"\']+)["\']/', $attrs, $matches) !== 1) {
            return null;
        }

        $id = trim($matches[1]);

        return $id !== '' ? $id : null;
    }

    /**
     * @param  array<string, true>  $used
     */
    private static function uniqueSlug(string $title, array $used): string
    {
        $base = self::slug($title);
        $id = $base;
        $n = 2;

        while (isset($used[$id])) {
            $id = $base.'-'.$n;
            $n++;
        }

        return $id;
    }

    private static function slug(string $text): string
    {
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'section';
    }

    private static function withoutPermalink(string $html): string
    {
        $stripped = preg_replace(
            '/<a\b[^>]*\bheading-permalink\b[^>]*>.*?<\/a>/is',
            '',
            $html,
        );

        return is_string($stripped) ? $stripped : $html;
    }

    private static function plainText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }
}
