<?php

declare(strict_types=1);

namespace Milon\Papyrus\Markdown;

final class HtmlPostProcessor
{
    public function __construct(
        private readonly int $breakLevel = 2,
    ) {}

    public function process(string $html, int $chapterIndex): string
    {
        $html = str_replace(
            '[break]',
            '<div style="page-break-after: always;"></div>',
            $html,
        );

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

        return str_replace('[break]', '<div style="page-break-after: always;"></div>', $html);
    }
}
