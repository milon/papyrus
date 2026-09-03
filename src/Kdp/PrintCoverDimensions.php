<?php

declare(strict_types=1);

namespace Milon\Papyrus\Kdp;

use Milon\Papyrus\Config\DocumentSize;

/**
 * Paperback wrap-cover estimates using Amazon KDP's published formulas.
 *
 * @see https://kdp.amazon.com/help/topic/G201953020
 */
final class PrintCoverDimensions
{
    public const BLEED_IN = 0.125;

    public const BLEED_MM = 3.175;

    public const SPINE_TEXT_MIN_PAGES = 79;

    /**
     * @var array<string, float> inches per page
     */
    private const PAPER_IN_PER_PAGE = [
        'white' => 0.002252,
        'cream' => 0.0025,
    ];

    /**
     * @return array{
     *     page_count: int,
     *     paper: string,
     *     bleed_in: float,
     *     bleed_mm: float,
     *     spine_in: float,
     *     spine_mm: float,
     *     wrap_width_in: float,
     *     wrap_height_in: float,
     *     wrap_width_mm: float,
     *     wrap_height_mm: float,
     *     spine_text_allowed: bool,
     *     formula: string
     * }
     */
    public static function calculate(DocumentSize $trim, int $pageCount, string $paper = 'white'): array
    {
        if ($pageCount < 1) {
            throw new KdpException('Page count must be at least 1 for cover dimensions.');
        }

        $paperKey = strtolower(trim($paper));

        if (! isset(self::PAPER_IN_PER_PAGE[$paperKey])) {
            $paperKey = 'white';
        }

        $inchesPerPage = self::PAPER_IN_PER_PAGE[$paperKey];
        $spineIn = $pageCount * $inchesPerPage;
        $trimWidthIn = $trim->widthMm / 25.4;
        $trimHeightIn = $trim->heightMm / 25.4;
        $wrapWidthIn = (2 * self::BLEED_IN) + (2 * $trimWidthIn) + $spineIn;
        $wrapHeightIn = (2 * self::BLEED_IN) + $trimHeightIn;

        return [
            'page_count' => $pageCount,
            'paper' => $paperKey,
            'bleed_in' => self::BLEED_IN,
            'bleed_mm' => self::BLEED_MM,
            'spine_in' => round($spineIn, 4),
            'spine_mm' => round($spineIn * 25.4, 3),
            'wrap_width_in' => round($wrapWidthIn, 4),
            'wrap_height_in' => round($wrapHeightIn, 4),
            'wrap_width_mm' => round($wrapWidthIn * 25.4, 3),
            'wrap_height_mm' => round($wrapHeightIn * 25.4, 3),
            'spine_text_allowed' => $pageCount >= self::SPINE_TEXT_MIN_PAGES,
            'formula' => sprintf('pages × %.6f in (%s paper) + %.3f in bleed each side', $inchesPerPage, $paperKey, self::BLEED_IN),
        ];
    }
}
