<?php

declare(strict_types=1);

namespace Milon\Papyrus\Render\Html;

use Milon\Papyrus\Config\Project;

final class HtmlRenderer
{
    private const TEMPLATE_FILE = 'theme-html.html';

    public function __construct(
        private readonly Project $project,
    ) {}

    public function render(?string $outputPath = null): string
    {
        $templatePath = $this->project->assetsDir.'/'.self::TEMPLATE_FILE;

        if (! is_file($templatePath)) {
            throw new HtmlException(sprintf('HTML theme not found: %s', $templatePath));
        }

        $template = file_get_contents($templatePath);

        if ($template === false) {
            throw new HtmlException(sprintf('Unable to read HTML theme: %s', $templatePath));
        }

        $book = $this->project->bookWithFigures(breakLevel: 1, exportTheme: 'default');

        $body = '';

        foreach ($book->chapters as $chapter) {
            $body .= $chapter->html;
        }

        $html = str_replace(
            ['{{$title}}', '{{$subtitle}}', '{{$author}}', '{{$body}}'],
            [
                htmlspecialchars($this->project->title(), ENT_QUOTES | ENT_HTML5),
                htmlspecialchars($this->project->subtitle(), ENT_QUOTES | ENT_HTML5),
                htmlspecialchars($this->project->author(), ENT_QUOTES | ENT_HTML5),
                $body,
            ],
            $template,
        );

        if (! is_dir($this->project->exportDir)) {
            mkdir($this->project->exportDir, 0755, true);
        }

        $filename = $outputPath ?? sprintf(
            '%s/%s.html',
            $this->project->exportDir,
            $this->project->outputSlug(),
        );

        if (file_put_contents($filename, $html) === false) {
            throw new HtmlException(sprintf('Unable to write HTML file: %s', $filename));
        }

        return $filename;
    }
}
