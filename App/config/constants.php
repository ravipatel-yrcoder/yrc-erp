<?php
return [
    'company' => [
        'location_types' => [
            'head_office' => 'Head Office',
            'branch' => 'Branch',
            'warehouse' => 'Warehouse',
            'store' => 'Store',
            'factory' => 'Factory',
            'workshop' => 'Workshop',
            'customer_site' => 'Customer Site',
            'vendor_site' => 'Vendor Site',
            'virtual' => 'Virtual'
        ]
    ],
    'crm' => [
        'lead_priorities' => [
            ['key' => 'high',   'label' => 'High',   'color' => 'danger'],
            ['key' => 'medium', 'label' => 'Medium',  'color' => 'warning'],
            ['key' => 'low',    'label' => 'Low',     'color' => 'secondary'],
        ],
        'lead_sources' => [
            ['key' => 'website',        'label' => 'Website'],
            ['key' => 'referral',       'label' => 'Referral'],
            ['key' => 'cold_call',      'label' => 'Cold Call'],
            ['key' => 'email_campaign', 'label' => 'Email Campaign'],
            ['key' => 'social_media',   'label' => 'Social Media'],
            ['key' => 'trade_show',     'label' => 'Trade Show'],
            ['key' => 'indiamart',      'label' => 'IndiaMART'],
            ['key' => 'other',          'label' => 'Other'],
        ],
    ],
    'inventory' => [
        'stock_movement_type' => [
            'adjust_in' => 'Adjust In',
            'adjust_out' => 'Adjust Out',
            'transfer_in' => 'Transfer In',
            'transfer_out' => 'Transfer Out',
            'purchase_receipt' => 'Purchase Receipt',
            'sale' => 'Sales Delivery',
            'return_from_customer' => 'Customer Return',
            'return_to_supplier' => 'Return to Supplier',
            'consume' => 'Consumption',
            'produce' => 'Production',
            'scrap' => 'Scrapped',
        ]
    ],
];