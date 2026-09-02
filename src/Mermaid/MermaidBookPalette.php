<?php

declare(strict_types=1);

namespace Milon\Papyrus\Mermaid;

/**
 * Diagram colours matched to Papyrus light/dark themes (Catppuccin Latte / Mocha),
 * same approach as laravel-after-deploy's BOOK_THEMES.
 */
final class MermaidBookPalette
{
    /**
     * @return array{theme: string, themeVariables: array<string, string>, flowchart: array<string, mixed>}
     */
    public static function config(string $variant): array
    {
        $variant = $variant === 'dark' ? 'dark' : 'light';

        return [
            'theme' => 'base',
            'themeVariables' => self::variables($variant),
            'flowchart' => [
                'curve' => 'basis',
                'padding' => 12,
                'wrappingWidth' => 500,
                'nodeSpacing' => 40,
                'rankSpacing' => 40,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function variables(string $variant): array
    {
        if ($variant === 'dark') {
            return [
                'background' => '#24273a',
                'mainBkg' => '#1e1e2e',
                'primaryColor' => '#1e1e2e',
                'primaryTextColor' => '#c1d4ea',
                'primaryBorderColor' => '#dd7878',
                'secondaryColor' => '#292c3c',
                'tertiaryColor' => '#303446',
                'nodeBorder' => '#dd7878',
                'nodeTextColor' => '#c1d4ea',
                'lineColor' => '#a5adcb',
                'textColor' => '#c1d4ea',
                'clusterBkg' => '#24273a',
                'clusterBorder' => '#24273a',
                'titleColor' => '#c1d4ea',
                'edgeLabelBackground' => '#24273a',
                'fontSize' => '15px',
            ];
        }

        return [
            'background' => '#ffffff',
            'mainBkg' => '#eff1f5',
            'primaryColor' => '#eff1f5',
            'primaryTextColor' => '#252525',
            'primaryBorderColor' => '#dd7878',
            'secondaryColor' => '#e6e9ef',
            'tertiaryColor' => '#dce0e8',
            'nodeBorder' => '#dd7878',
            'nodeTextColor' => '#252525',
            'lineColor' => '#5c5f77',
            'textColor' => '#252525',
            'clusterBkg' => '#ffffff',
            'clusterBorder' => '#ffffff',
            'titleColor' => '#252525',
            'edgeLabelBackground' => '#ffffff',
            'fontSize' => '15px',
        ];
    }
}
