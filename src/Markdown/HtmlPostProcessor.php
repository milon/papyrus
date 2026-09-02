<?php

declare(strict_types=1);

namespace Milon\Papyrus\Markdown;

final class HtmlPostProcessor
{
    private const BREAK_HTML = '<div style="page-break-after: always;"></div>';

    public function __construct(
        private readonly int $breakLevel = 2,
    ) {}

    public function process(string $html, int $chapterIndex): string
    {
        // Replace user markers first, but never inside <pre>/<code> — books that
        // document "[break]" would otherwise inject block <div>s into inline/code
        // HTML and send mPDF into a pathological parse loop.
        $html = $this->replaceBreaksOutsideCode($html);

        if ($chapterIndex > 0 && $this->breakLevel >= 1) {
            $html = str_replace('<h1>', '[break]<h1>', $html);
        }

        if ($this->breakLevel >= 2) {
            $html = str_replace('<h2>', '[break]<h2>', $html);
        }

        $html = str_replace(
            [
                "<blockquote>\n<p>{notice}",
                "<blockquote>\n<p>{warning}",
                "<blockquote>\n<p>{quote}",
                "<blockquote>\n<p>[!NOTE]",
                "<blockquote>\n<p>[!WARNING]",
            ],
            [
                "<blockquote class='notice'><p><strong>Notice:</strong>",
                "<blockquote class='warning'><p><strong>Warning:</strong>",
                "<blockquote class='quote'><p>",
                "<blockquote class='notice'><p><strong>Note:</strong>",
                "<blockquote class='warning'><p><strong>Warning:</strong>",
            ],
            $html,
        );

        return $this->replaceBreaksOutsideCode($html);
    }

    private function replaceBreaksOutsideCode(string $html): string
    {
        $parts = preg_split(
            '/(<pre\b[^>]*>.*?<\/pre>|<code\b[^>]*>.*?<\/code>)/is',
            $html,
            -1,
            PREG_SPLIT_DELIM_CAPTURE,
        );

        if ($parts === false) {
            return str_replace('[break]', self::BREAK_HTML, $html);
        }

        foreach ($parts as $i => $part) {
            if ($i % 2 === 1) {
                continue;
            }

            $parts[$i] = str_replace('[break]', self::BREAK_HTML, $part);
        }

        return implode('', $parts);
    }
}
