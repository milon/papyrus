<?php

declare(strict_types=1);

namespace Milon\Papyrus\Config;

final class DocumentPreset
{
    /**
     * @param  list<string>  $aliases
     */
    public function __construct(
        public readonly string $name,
        public readonly float $widthMm,
        public readonly float $heightMm,
        public readonly string $inchesLabel,
        public readonly array $aliases = [],
    ) {}

    public function matches(float $widthMm, float $heightMm, float $tolerance = 0.01): bool
    {
        return abs($this->widthMm - $widthMm) <= $tolerance
            && abs($this->heightMm - $heightMm) <= $tolerance;
    }
}
