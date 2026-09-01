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

        $theme = is_string($mermaid['theme'] ?? null) ? $mermaid['theme'] : 'auto';

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

    public function resolvedTheme(string $exportTheme): string
    {
        if ($this->theme !== 'auto') {
            return $this->theme;
        }

        return $exportTheme === 'dark' ? 'dark' : 'default';
    }
}
