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

    // Optional cover assets under assets/.
    // 'cover' => [
    //     'image' => 'cover.png',
    //     'light' => 'cover-light.png',
    //     'dark' => 'cover-dark.png',
    // ],

    // Optional site home extras for `build:site` (banner under assets/).
    // 'site' => [
    //     'banner' => 'banner.jpg',
    //     'lead' => 'A one-line pitch for the home page.',
    //     'cname' => 'docs.example.com', // writes CNAME for GitHub Pages
    //     'base_path' => '/my-repo', // project Pages path prefix; omit for domain root
    //     'links' => [
    //         ['label' => 'Downloads', 'chapter' => '19-downloads.md'],
    //         ['label' => 'Source', 'url' => 'https://github.com/you/your-book'],
    //     ],
    //     // 'repository' => 'https://github.com/you/your-book', // legacy fallback for auto links
    // ],

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
            'enabled' => false,
            // 'cover' => 'cover-ebook.jpg',
        ],
        'print' => [
            'enabled' => false,
            'bleed_mm' => 3,
        ],
    ],

    'sample' => [
        'ranges' => [
            ['from' => 1, 'to' => 3],
        ],
        // 'chapters' => ['01-introduction.md'],
    ],

    'sample_notice' => 'This is a sample from My Book.',
];
