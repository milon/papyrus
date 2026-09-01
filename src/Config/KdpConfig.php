<?php

declare(strict_types=1);

namespace Milon\Papyrus\Config;

final class KdpConfig
{
    /**
     * @param  list<string>  $keywords
     */
    public function __construct(
        public readonly bool $ebookEnabled,
        public readonly ?string $ebookCover,
        public readonly bool $printEnabled,
        public readonly float $printBleedMm,
        public readonly string $printMarginPreset,
        public readonly string $printPaper,
        public readonly string $metadataDescription,
        public readonly array $keywords,
        public readonly ?string $metadataLanguage,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(array $config): self
    {
        $kdp = $config['kdp'] ?? [];

        if (! is_array($kdp)) {
            $kdp = [];
        }

        $ebook = is_array($kdp['ebook'] ?? null) ? $kdp['ebook'] : [];
        $print = is_array($kdp['print'] ?? null) ? $kdp['print'] : [];
        $metadata = is_array($kdp['metadata'] ?? null) ? $kdp['metadata'] : [];

        $keywords = $metadata['keywords'] ?? [];

        return new self(
            ebookEnabled: (bool) ($ebook['enabled'] ?? false),
            ebookCover: self::optionalString($ebook['cover'] ?? null),
            printEnabled: (bool) ($print['enabled'] ?? false),
            printBleedMm: self::floatValue($print['bleed_mm'] ?? 3),
            printMarginPreset: is_string($print['margin_preset'] ?? null) ? $print['margin_preset'] : 'recommended',
            printPaper: is_string($print['paper'] ?? null) ? $print['paper'] : 'white',
            metadataDescription: is_string($metadata['description'] ?? null) ? $metadata['description'] : '',
            keywords: is_array($keywords) ? array_values(array_filter(array_map('strval', $keywords))) : [],
            metadataLanguage: self::optionalString($metadata['language'] ?? null),
        );
    }

    public function hasEnabledOutputs(): bool
    {
        return $this->ebookEnabled || $this->printEnabled;
    }

    private static function optionalString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function floatValue(mixed $value): float
    {
        return is_int($value) || is_float($value) ? (float) $value : (float) (string) $value;
    }
}
