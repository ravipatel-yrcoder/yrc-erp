<?php

return [

    'sales_order' => [
        'template_1' => [
            'label'       => 'Template 1',
            'description' => 'Standard layout with a 3-column header block showing document info, billing address, and shipping address side by side.',
            'view'        => 'pdf.sales-order',
            'thumbnail'   => null,
        ],
        'template_2' => [
            'label'       => 'Template 2',
            'description' => 'Two-column header with company info on the left and document title prominent on the right, address blocks and a meta row below.',
            'view'        => 'pdf.sales-order--template_2',
            'thumbnail'   => null,
        ],
    ],

    'purchase_order' => [
        'template_1' => [
            'label'       => 'Template 1',
            'description' => 'Standard layout with a 3-column header block showing document info, vendor details, and shipping address side by side.',
            'view'        => 'pdf.purchase-order',
            'thumbnail'   => null,
        ],
        'template_2' => [
            'label'       => 'Template 2',
            'description' => 'Two-column header with company info on the left and document title prominent on the right, address blocks and a meta row below.',
            'view'        => 'pdf.purchase-order--template_2',
            'thumbnail'   => null,
        ],
    ],

];
