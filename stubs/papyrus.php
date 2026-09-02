<?php

declare(strict_types=1);

return [
    'title' => 'My Book',
    'subtitle' => 'A short subtitle',
    'author' => 'Author Name',
    'themes' => ['light', 'dark'],

    'document' => [
        'size' => 'crown-quarto',
        'margin_left' => 27,
        'margin_right' => 27,
        'margin_top' => 14,
        'margin_bottom' => 14,
    ],

    'toc' => [
        'h1' => 0,
        'h2' => 0,
        'h3' => 1,
    ],

    'cover' => [
        'image' => 'cover.png',
        'light' => 'cover-light.png',
        'dark' => 'cover-dark.png',
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
            [
                'name' => 'notosansbengali',
                'regular' => 'NotoSansBengali-Regular.ttf',
                'bold' => 'NotoSansBengali-Bold.ttf',
                'otl' => true,
            ],
        ],
        'script' => [
            ['match' => ['bn', 'ben', 'bengali'], 'face' => 'notosansbengali'],
        ],
    ],

    'mermaid' => [
        'enabled' => true,
        'format' => 'svg',
        // Default: book colours for PDF light/dark; HTML/site embed both variants.
        // Set to default|dark|forest|neutral for Mermaid stock themes instead.
        'theme' => 'auto',
        'max_width_mm' => 130,
    ],

    'kdp' => [
        'ebook' => [
            'enabled' => true,
            'cover' => 'cover-ebook.jpg',
        ],
        'print' => [
            'enabled' => true,
            'bleed_mm' => 3,
        ],
    ],

    'sample' => [
        'ranges' => [
            ['from' => 1, 'to' => 3],
        ],
    ],

    'sample_notice' => 'This is a sample from My Book.',
];
