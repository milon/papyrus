<?php

declare(strict_types=1);

namespace Milon\Papyrus\Config;

final class KdpTrimBounds
{
    public const MIN_WIDTH_MM = 101.6;

    public const MAX_WIDTH_MM = 215.9;

    public const MIN_HEIGHT_MM = 152.4;

    public const MAX_HEIGHT_MM = 279.4;

    public static function isWithinBounds(DocumentSize $size): bool
    {
        return $size->widthMm >= self::MIN_WIDTH_MM
            && $size->widthMm <= self::MAX_WIDTH_MM
            && $size->heightMm >= self::MIN_HEIGHT_MM
            && $size->heightMm <= self::MAX_HEIGHT_MM;
    }
}
