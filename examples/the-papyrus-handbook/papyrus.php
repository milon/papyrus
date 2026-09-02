<?php

declare(strict_types=1);

return [
    'title' => 'The Papyrus Handbook',
    'subtitle' => 'Markdown to Book — PDF, EPUB, HTML, and KDP',
    'author' => 'Papyrus',
    'themes' => ['light'],

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
        'image' => 'cover.jpg',
    ],

    'fonts' => [
        'script' => [
            ['match' => ['bn', 'ben', 'bengali'], 'face' => 'notosansbengali'],
        ],
    ],

    'mermaid' => [
        'enabled' => false,
        'format' => 'svg',
        'theme' => 'auto',
        'max_width_mm' => 130,
    ],

    'sample' => [
        'ranges' => [
            ['from' => 1, 'to' => 4],
        ],
    ],

    'sample_notice' => 'This is a sample from The Papyrus Handbook.',

    'kdp' => [
        'ebook' => [
            'enabled' => false,
        ],
        'print' => [
            'enabled' => false,
        ],
        'metadata' => [
            'description' => 'Official handbook for milon/papyrus — turn Markdown into PDF, EPUB, HTML, and Amazon KDP exports.',
            'keywords' => ['papyrus', 'markdown', 'ebook', 'pdf', 'kdp'],
            'language' => 'en',
        ],
    ],
];
