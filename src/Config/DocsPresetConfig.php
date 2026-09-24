<?php

declare(strict_types=1);

namespace Milon\Papyrus\Config;

/**
 * Site-first config for {@see InitPreset::Docs} (no KDP / sample defaults).
 */
final class DocsPresetConfig
{
    /**
     * @return array<string, mixed>
     */
    public static function build(?ComposerProjectHint $hint = null): array
    {
        $title = $hint?->displayTitle() ?? 'My Package';
        $lead = $hint?->description !== null && $hint->description !== ''
            ? $hint->description
            : 'Documentation for this package.';
        $basePath = $hint?->suggestedBasePath() ?? '/your-repo';

        $links = [];

        if ($hint?->sourceUrl !== null) {
            $links[] = ['label' => 'GitHub', 'url' => $hint->sourceUrl];
        } else {
            $links[] = ['label' => 'GitHub', 'url' => 'https://github.com/you/your-package'];
        }

        if ($hint !== null) {
            $links[] = ['label' => 'Packagist', 'url' => $hint->packagistUrl()];
        } else {
            $links[] = ['label' => 'Packagist', 'url' => 'https://packagist.org/packages/you/your-package'];
        }

        if ($hint?->homepage !== null) {
            $links[] = ['label' => 'Homepage', 'url' => $hint->homepage];
        }

        return [
            'title' => $title,
            'subtitle' => '',
            'author' => '',
            'themes' => ['light', 'dark'],

            'document' => [
                'size' => 'a4',
                'margin_left' => 25,
                'margin_right' => 25,
                'margin_top' => 20,
                'margin_bottom' => 20,
            ],

            'toc' => [
                'h1' => 0,
                'h2' => 0,
                'h3' => 1,
            ],

            'site' => [
                'mode' => 'docs',
                'lead' => $lead,
                // Project GitHub Pages: set to /<repo>. Omit (or use cname) for a custom domain.
                'base_path' => $basePath,
                'links' => $links,
                'nav' => [
                    [
                        'group' => 'Getting started',
                        'chapters' => ['00-welcome.md', '01-install.md', '02-usage.md'],
                    ],
                    [
                        'group' => 'Reference',
                        'chapters' => ['03-api.md', '04-changelog.md'],
                    ],
                ],
            ],

            'header' => [
                'style' => 'font-style: italic; text-align: right; border-bottom: solid 1px #808080;',
            ],

            'fonts' => [
                'faces' => [
                    [
                        'name' => 'librelibertine',
                        'regular' => 'LinLibertine_R.ttf',
                        'bold' => 'LinLibertine_RB.ttf',
                        'italic' => 'LinLibertine_RI.ttf',
                        'bold_italic' => 'LinLibertine_RBI.ttf',
                    ],
                    [
                        'name' => 'oxproto',
                        'regular' => '0xProto-Regular.ttf',
                        'bold' => '0xProto-Bold.ttf',
                        'italic' => '0xProto-Italic.ttf',
                        'otl' => true,
                    ],
                ],
                'script' => [],
            ],

            'mermaid' => [
                'enabled' => true,
                'format' => 'svg',
                'theme' => 'auto',
                'max_width_mm' => 130,
            ],
        ];
    }
}
