<?php

declare(strict_types=1);

return [
    'title' => 'Mini Book',
    'subtitle' => 'Papyrus fixture',
    'author' => 'Papyrus',
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
        'image' => 'cover.png',
        'light' => 'cover-light.png',
        'dark' => 'cover-dark.png',
    ],

    'fonts' => [
        'script' => [
            ['match' => ['bn', 'ben', 'bengali'], 'face' => 'notosansbengali'],
        ],
    ],

    'mermaid' => [
        'enabled' => false,
    ],
];
