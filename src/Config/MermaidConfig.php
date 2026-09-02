<?php

declare(strict_types=1);

namespace Milon\Papyrus\Config;

final class MermaidConfig
{
    public function __construct(
        public readonly bool $enabled,
        public readonly string $format,
        public readonly string $theme,
        public readonly float $maxWidthMm,
        public readonly ?string $command,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(array $config): self
    {
        $mermaid = $config['mermaid'] ?? [];

        if (! is_array($mermaid)) {
            $mermaid = [];
        }

        $format = is_string($mermaid['format'] ?? null) ? strtolower($mermaid['format']) : 'svg';

        if (! in_array($format, ['svg', 'png'], true)) {
            $format = 'svg';
        }

        $theme = is_string($mermaid['theme'] ?? null) ? trim($mermaid['theme']) : 'auto';

        if ($theme === '') {
            $theme = 'auto';
        }

        $maxWidth = $mermaid['max_width_mm'] ?? 130;

        return new self(
            enabled: (bool) ($mermaid['enabled'] ?? false),
            format: $format,
            theme: $theme,
            maxWidthMm: is_int($maxWidth) || is_float($maxWidth) ? (float) $maxWidth : (float) (string) $maxWidth,
            command: is_string($mermaid['command'] ?? null) && $mermaid['command'] !== ''
                ? $mermaid['command']
                : null,
        );
    }

    /**
     * When true, diagrams use book-matched colours (Catppuccin light/dark)
     * instead of Mermaid stock themes. This is the default (`theme` = `auto`).
     */
    public function usesBookPalette(): bool
    {
        return $this->theme === 'auto';
    }

    /**
     * HTML / site builds toggle light and dark; embed both diagram variants.
     * Default for `theme` = `auto` (and therefore for omitted `theme`).
     */
    public function isDualExport(string $exportTheme): bool
    {
        return $this->usesBookPalette() && $exportTheme === 'html';
    }

    /**
     * Book palette variant (`light` / `dark`) when theme is `auto`.
     */
    public function bookVariant(string $exportTheme): string
    {
        return $exportTheme === 'dark' ? 'dark' : 'light';
    }

    /**
     * Mermaid CLI `-t` theme when not using the book palette.
     */
    public function resolvedTheme(string $exportTheme): string
    {
        if (! $this->usesBookPalette()) {
            return $this->theme;
        }

        return $this->bookVariant($exportTheme);
    }
}
