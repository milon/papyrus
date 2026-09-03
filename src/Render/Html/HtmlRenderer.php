<?php

declare(strict_types=1);

namespace Milon\Papyrus\Render\Html;

use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Markdown\HeadingAnchors;

final class HtmlRenderer
{
    private const TEMPLATE_FILE = 'theme-html.html';

    public function __construct(
        private readonly Project $project,
    ) {}

    public function render(?string $outputPath = null): string
    {
        $templatePath = $this->project->assetPath(self::TEMPLATE_FILE);

        if ($templatePath === null) {
            throw new HtmlException(sprintf('HTML theme not found: %s', self::TEMPLATE_FILE));
        }

        $template = file_get_contents($templatePath);

        if ($template === false) {
            throw new HtmlException(sprintf('Unable to read HTML theme: %s', $templatePath));
        }

        $book = $this->project->bookWithFigures(breakLevel: 1, exportTheme: 'html');

        $body = '';

        foreach ($book->chapters as $chapter) {
            $body .= HeadingAnchors::decorate($chapter->html);
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

        $html = $this->embedFonts($html);

        if (file_put_contents($filename, $html) === false) {
            throw new HtmlException(sprintf('Unable to write HTML file: %s', $filename));
        }

        return $filename;
    }

    private function embedFonts(string $html): string
    {
        return (string) preg_replace_callback(
            '/url\(\s*(["\']?)(\.\.\/assets\/fonts\/([^"\')\s]+))\1\s*\)/i',
            function (array $matches): string {
                $relative = $matches[3];
                $path = $this->project->assetPath('fonts/'.$relative);

                if ($path === null || ! is_file($path)) {
                    return $matches[0];
                }

                $contents = file_get_contents($path);

                if ($contents === false) {
                    return $matches[0];
                }

                $extension = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
                $mime = match ($extension) {
                    'ttf' => 'font/ttf',
                    'otf' => 'font/otf',
                    'woff' => 'font/woff',
                    'woff2' => 'font/woff2',
                    default => 'application/octet-stream',
                };

                return 'url("data:'.$mime.';base64,'.base64_encode($contents).'")';
            },
            $html,
        );
    }
}
