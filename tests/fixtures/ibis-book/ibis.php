<?php

declare(strict_types=1);

return [
    'title' => 'Ibis Fixture Book',
    'subtitle' => 'Migration test fixture',
    'author' => 'Papyrus',

    'fonts' => [
        'librelibertine' => [
            'R' => 'LinLibertine_R.ttf',
            'B' => 'LinLibertine_RB.ttf',
            'I' => 'LinLibertine_RI.ttf',
            'BI' => 'LinLibertine_RBI.ttf',
        ],
        'oxproto' => [
            'R' => '0xProto-Regular.ttf',
            'useOTL' => 0xFF,
        ],
        'notosansbengali' => [
            'R' => 'NotoSansBengali-Regular.ttf',
            'useOTL' => 0xFF,
        ],
    ],

    'document' => [
        'format' => [188.976, 246.126],
        'margin_left' => 27,
        'margin_right' => 27,
        'margin_bottom' => 14,
        'margin_top' => 14,
    ],

    'toc_levels' => [
        'H1' => 0,
        'H2' => 0,
        'H3' => 1,
    ],

    'cover' => [
        'image' => 'cover.png',
    ],

    'sample' => [
        [1, 4],
    ],

    'header' => 'font-style: italic; text-align: right; border-bottom: solid 1px #808080;',

    'sample_notice' => 'This is a sample from Ibis Fixture Book.',
];
