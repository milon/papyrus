<?php

declare(strict_types=1);

namespace Milon\Papyrus\Config;

final class DocumentSize
{
    /**
     * @var array<string, array{width: float, height: float}>
     */
    private const PRESETS = [
        'a4' => ['width' => 210.0, 'height' => 297.0],
        'a5' => ['width' => 148.0, 'height' => 210.0],
        'letter' => ['width' => 215.9, 'height' => 279.4],
        '6x9' => ['width' => 152.4, 'height' => 228.6],
        'crown-quarto' => ['width' => 188.976, 'height' => 246.126],
    ];

    public function __construct(
        public readonly float $widthMm,
        public readonly float $heightMm,
        public readonly float $marginLeft,
        public readonly float $marginRight,
        public readonly float $marginTop,
        public readonly float $marginBottom,
    ) {}

    /**
     * @param  array<string, mixed>  $document
     */
    public static function fromConfig(array $document): self
    {
        [$width, $height] = self::resolveDimensions($document);

        return new self(
            widthMm: $width,
            heightMm: $height,
            marginLeft: self::floatValue($document['margin_left'] ?? 27),
            marginRight: self::floatValue($document['margin_right'] ?? 27),
            marginTop: self::floatValue($document['margin_top'] ?? 14),
            marginBottom: self::floatValue($document['margin_bottom'] ?? 14),
        );
    }

    /**
     * @return list<string>
     */
    public static function presetNames(): array
    {
        return array_keys(self::PRESETS);
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array{0: float, 1: float}
     */
    private static function resolveDimensions(array $document): array
    {
        if (isset($document['format']) && is_array($document['format'])) {
            return [
                self::floatValue($document['format'][0] ?? 210),
                self::floatValue($document['format'][1] ?? 297),
            ];
        }

        $size = (string) ($document['size'] ?? 'a4');
        $preset = self::PRESETS[$size] ?? null;

        if ($preset === null) {
            throw new ConfigException(sprintf(
                'Unknown document size "%s". Known presets: %s',
                $size,
                implode(', ', self::presetNames()),
            ));
        }

        return [$preset['width'], $preset['height']];
    }

    private static function floatValue(mixed $value): float
    {
        return is_int($value) || is_float($value) ? (float) $value : (float) (string) $value;
    }
}
