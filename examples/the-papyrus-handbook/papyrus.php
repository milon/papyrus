<?php

declare(strict_types=1);

return [
    'title' => 'The Papyrus Handbook',
    'subtitle' => 'Markdown to PDF, EPUB, HTML sites, and KDP',
    'author' => 'Nuruzzaman Milon',
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

    'header' => [
        'style' => 'font-style: italic; text-align: right; border-bottom: solid 1px #808080;',
    ],

    'cover' => [
        'image' => 'cover.jpg', // book-specific; themes/fonts use bundled defaults
    ],

    'site' => [
        'banner' => 'banner.jpg', // book-specific site home image
        'lead' => 'Official handbook for milon/papyrus — the same Markdown chapters build the PDF, HTML file, and this site.',
        'cname' => 'papyrus.milon.im',
        'links' => [
            ['label' => 'Downloads', 'chapter' => '19-downloads.md'],
            ['label' => 'Source on GitHub', 'url' => 'https://github.com/milon/papyrus'],
            ['label' => 'Packagist', 'url' => 'https://packagist.org/packages/milon/papyrus'],
            ['label' => 'Issues', 'url' => 'https://github.com/milon/papyrus/issues'],
        ],
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
        // theme defaults to auto (book colours; HTML/site embed light+dark)
        'format' => 'svg',
        'theme' => 'auto',
        'max_width_mm' => 130,
    ],

    'sample' => [
        // Cover, title page, and pretoc (Welcome) — stops before the TOC.
        'ranges' => [
            ['from' => 1, 'to' => 5],
        ],
        // Then whole chapters: build:html (09) and kdp:cover (15).
        'chapters' => [
            '09-build-html.md',
            '15-kdp-cover.md',
        ],
    ],

    'sample_notice' => 'This is a sample from The Papyrus Handbook — https://github.com/milon/papyrus',

    'kdp' => [
        'ebook' => [
            'enabled' => false,
        ],
        'print' => [
            'enabled' => false,
        ],
        'metadata' => [
            'description' => 'Official handbook for milon/papyrus — turn Markdown into PDF, EPUB, HTML sites, and Amazon KDP exports. https://github.com/milon/papyrus',
            'keywords' => ['papyrus', 'markdown', 'ebook', 'pdf', 'kdp', 'static-site'],
            'language' => 'en',
        ],
    ],
];
