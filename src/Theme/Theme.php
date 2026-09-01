<?php

declare(strict_types=1);

namespace Milon\Papyrus\Theme;

final class Theme
{
    private const TOC_MARKERS = [
        '<!-- PAPYRUS:TOC -->',
        '<!-- IBIS:TOC -->',
    ];

    private const DEFAULT_TOC = <<<'HTML'
<tocpagebreak links="on"
              toc-suppress="on"
              toc-preHTML="&lt;h1&gt;Contents&lt;/h1&gt;"
              toc-bookmarkText="Contents">
HTML;

    public function __construct(
        public readonly string $head,
        public readonly string $tail,
    ) {}

    public function preamble(): string
    {
        return $this->head.$this->tail;
    }

    /**
     * @param  array<string, string>  $replacements
     */
    public static function load(string $path, array $replacements): self
    {
        if (! is_file($path)) {
            throw new ThemeException(sprintf('Theme file not found: %s', $path));
        }

        $html = file_get_contents($path);

        if ($html === false) {
            throw new ThemeException(sprintf('Unable to read theme file: %s', $path));
        }

        $html = str_replace(array_keys($replacements), array_values($replacements), $html);

        foreach (self::TOC_MARKERS as $marker) {
            if (! str_contains($html, $marker)) {
                continue;
            }

            [$head, $tail] = explode($marker, $html, 2);
            $tail = trim($tail);

            if ($tail === '') {
                $tail = self::DEFAULT_TOC;
            }

            return new self($head, $tail);
        }

        throw new ThemeException(sprintf(
            'Theme must contain a TOC marker (%s).',
            implode(' or ', self::TOC_MARKERS),
        ));
    }
}
