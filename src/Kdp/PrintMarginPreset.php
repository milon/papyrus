<?php

declare(strict_types=1);

namespace Milon\Papyrus\Kdp;

use Milon\Papyrus\Config\DocumentSize;

final class PrintMarginPreset
{
    /**
     * @var array<string, array{left: float, right: float, top: float, bottom: float}>
     */
    private const PRESETS = [
        'recommended' => [
            'left' => 12.7,
            'right' => 9.525,
            'top' => 12.7,
            'bottom' => 12.7,
        ],
        'minimum' => [
            'left' => 9.525,
            'right' => 6.35,
            'top' => 9.525,
            'bottom' => 9.525,
        ],
    ];

    /**
     * @return array{left: float, right: float, top: float, bottom: float}
     */
    public static function margins(string $preset): array
    {
        return self::PRESETS[$preset] ?? self::PRESETS['recommended'];
    }

    public static function documentWithBleed(DocumentSize $trim, string $preset, float $bleedMm): DocumentSize
    {
        $margins = self::margins($preset);

        return new DocumentSize(
            widthMm: $trim->widthMm + (2 * $bleedMm),
            heightMm: $trim->heightMm + (2 * $bleedMm),
            marginLeft: $margins['left'] + $bleedMm,
            marginRight: $margins['right'] + $bleedMm,
            marginTop: $margins['top'] + $bleedMm,
            marginBottom: $margins['bottom'] + $bleedMm,
        );
    }
}
