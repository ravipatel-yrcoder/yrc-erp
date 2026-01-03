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
    'inventory' => [
        'stock_movement_type' => [
            'adjust_in' => 'Stock Addition',
            'adjust_out' => 'Stock Removal',
            'transfer_in' => 'Transfer In',
            'transfer_out' => 'Transfer Out',
            'purchase' => 'Purchase Receipt',
            'sale' => 'Sales Delivery',
            'return_from_customer' => 'Customer Return',
            'return_to_supplier' => 'Return to Supplier',
            'consume' => 'Consumption',
            'produce' => 'Production',
            'scrap' => 'Scrapped',
        ]
    ],
];