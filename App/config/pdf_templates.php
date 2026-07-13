<?php

return [

    'quotation' => [
        'template_1' => [
            'label'       => 'Default',
            'description' => 'Two-column header with company info on the left and document title prominent on the right, with address blocks below.',
            'view'        => 'pdf.quotation',
            'thumbnail'   => null,
        ],
    ],

    'sales_order' => [
        'template_1' => [
            'label'       => 'Default',
            'description' => 'Two-column header with company info on the left and document title prominent on the right, with address blocks below.',
            'view'        => 'pdf.sales-order',
            'thumbnail'   => null,
        ],
    ],

    'purchase_order' => [
        'template_1' => [
            'label'       => 'Default',
            'description' => 'Two-column header with company info on the left and document title prominent on the right, with address blocks below.',
            'view'        => 'pdf.purchase-order',
            'thumbnail'   => null,
        ],
    ],

    'rfq' => [
        'template_1' => [
            'label'       => 'Default',
            'description' => 'Two-column header with document title on the right. Shows Item, Expected Date, and Qty — no pricing. Includes vendor details and a disclaimer footer.',
            'view'        => 'pdf.rfq',
            'thumbnail'   => null,
        ],
    ],

];
