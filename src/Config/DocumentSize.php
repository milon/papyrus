<?php

declare(strict_types=1);

namespace Milon\Papyrus\Config;

final class DocumentSize
{
    /**
     * @var list<DocumentPreset>
     */
    private const PRESET_CATALOG = [
        ['name' => '5x8', 'width' => 127.0, 'height' => 203.2, 'inches' => '5×8'],
        ['name' => '5.5x8.5', 'width' => 139.7, 'height' => 215.9, 'inches' => '5.5×8.5', 'aliases' => ['digest']],
        ['name' => '6x9', 'width' => 152.4, 'height' => 228.6, 'inches' => '6×9', 'aliases' => ['trade']],
        ['name' => '7x10', 'width' => 177.8, 'height' => 254.0, 'inches' => '7×10'],
        ['name' => 'crown-quarto', 'width' => 188.976, 'height' => 246.126, 'inches' => '7.44×9.69', 'aliases' => ['7.44x9.69']],
        ['name' => 'letter', 'width' => 215.9, 'height' => 279.4, 'inches' => '8.5×11', 'aliases' => ['8.5x11']],
        ['name' => 'a4', 'width' => 210.0, 'height' => 297.0, 'inches' => '8.27×11.69'],
        ['name' => 'a5', 'width' => 148.0, 'height' => 210.0, 'inches' => '5.83×8.27'],
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
     * @return list<DocumentPreset>
     */
    public static function presets(): array
    {
        return array_map(
            static fn (array $preset): DocumentPreset => new DocumentPreset(
                name: $preset['name'],
                widthMm: $preset['width'],
                heightMm: $preset['height'],
                inchesLabel: $preset['inches'],
                aliases: $preset['aliases'] ?? [],
            ),
            self::PRESET_CATALOG,
        );
    }

    /**
     * @return list<string>
     */
    public static function presetNames(): array
    {
        $names = [];

        foreach (self::presets() as $preset) {
            $names[] = $preset->name;

            foreach ($preset->aliases as $alias) {
                $names[] = $alias;
            }
        }

        return $names;
    }

    public static function resolvePresetName(float $widthMm, float $heightMm): ?string
    {
        foreach (self::presets() as $preset) {
            if ($preset->matches($widthMm, $heightMm)) {
                return $preset->name;
            }
        }

        return null;
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

        foreach (self::presets() as $preset) {
            if ($size === $preset->name || in_array($size, $preset->aliases, true)) {
                return [$preset->widthMm, $preset->heightMm];
            }
        }

        throw new ConfigException(sprintf(
            'Unknown document size "%s". Known presets: %s',
            $size,
            implode(', ', array_map(static fn (DocumentPreset $preset): string => $preset->name, self::presets())),
        ));
    }

    private static function floatValue(mixed $value): float
    {
        return is_int($value) || is_float($value) ? (float) $value : (float) (string) $value;
    }
}
