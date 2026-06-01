<?php
return [
    'paper'         => 'A4',
    'margin_left'   => 14,
    'margin_right'  => 14,
    'margin_top'    => 14,
    'margin_bottom' => 14,
    'margin_footer' => 8,
    'font_dir'      => ROOT_PATH . '/Public/assets/fonts/pdf',
    'temp_dir'      => ROOT_PATH . '/App/storage/pdf-temp',
    'default_font'  => 'notosans',
    'fonts'         => [
        'notosans' => [
            'R'  => 'NotoSans-Regular.ttf',
            'B'  => 'NotoSans-Bold.ttf',
            'I'  => 'NotoSans-Italic.ttf',
            'BI' => 'NotoSans-BoldItalic.ttf',
        ],
        'notosansmedium' => [
            'R'  => 'NotoSans-Medium.ttf',
        ],
        'notosanssemibold' => [
            'R'  => 'NotoSans-SemiBold.ttf',
        ],
        'notosansdevanagari' => [
            'R'  => 'NotoSansDevanagari-Regular.ttf',
            'B'  => 'NotoSansDevanagari-Bold.ttf',
        ],
    ],
];
