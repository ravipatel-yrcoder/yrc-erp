<?php
return [
    "module" => "api",
    "prefix" => "api",
    "routes" => [
        "auth" => [
            [
                "pattern" => "/auth/refresh-token",
                "action" => "refreshToken",
            ],
        ],
        "prodcategories" => [
            [
                "pattern" => "/product-categories",
                "action" => "index",
            ],
            [
                "pattern" => "/product-categories/form-context",
                "action" => "formContext",
            ],
            [
                "pattern" => "/product-categories/:id",
                "name" => "single-category",
                "action" => "index",
            ]
        ],
        "productmasters" => [
            [
                "pattern" => "/product-masters",
                "action" => "index",
            ],
        ],
        "products" => [
            [
                "pattern" => "/products",
                "action" => "index",
            ],
            [
                "pattern" => "/products/form-context",
                "action" => "formContext",
            ],
            [
                "pattern" => "/products/:id",
                "name" => "single-product",
                "action" => "index",
            ],
        ],        
        "locations" => [
            [
                "pattern" => "/company/locations",
                "action" => "index",
            ],
            [
                "pattern" => "/company/locations/form-context",
                "action" => "formContext",
            ],
            [
                "pattern" => "/company/locations/:id",
                "name" => "single-location",
                "action" => "index",
            ]
        ],
        /* Start - Inventory module */
        "invproducts" => [
            [
                "pattern" => "/inv/products/:id/stock-locations", // :id is product id
                "name" => "prod-stock-locations",
                "action" => "stockLocations",
            ],
            [
                "pattern" => "/inv/products/:id/stock/adjust", // :id is product id
                "name" => "prod-adjust-stock",
                "action" => "adjustStock",
            ],
            [
                "pattern" => "/inv/products/:id/stock/adjust/form-context", // :id is product id
                "name" => "prod-add-edit-stock-location",
                "action" => "adjustFormContext",
            ],
            [
                "pattern" => "/inv/products/:id/serial-or-lot-numbers", // :id is product id
                "name" => "prod-serial-or-lot-numbers",
                "action" => "serialOrLotNumbers",
            ],

        ],
        "invsequance" => [
            [
                "pattern" => "/inv/sequance/generate",
                "action" => "generate",
            ],
        ],
        /* End - Inventory module */

        /* Start - Purchasing module */
        "vendors" => [
            [
                "pattern" => "/vendors",
                "name" => "vendors",
                "action" => "index",
            ],
            [
                "pattern" => "/vendors/form-context",
                "name" => "vendors-form-context",
                "action" => "formContext",
            ],
            [
                "pattern" => "/vendors/:id",
                "name" => "single-vendor",
                "action" => "index",
            ],
        ],
        "purchaseorders" => [
            [
                "pattern" => "/purchase-orders",
                "name" => "purchase-orders",
                "action" => "index",
            ],
            [
                "pattern" => "/purchase-orders/form-context",
                "name" => "po-form-context",
                "action" => "formContext",
            ],
            [
                "pattern" => "/purchase-orders/:id",
                "name" => "single-purchase-order",
                "action" => "entity",
            ],
            [
                "pattern" => "/purchase-orders/:id/status",
                "name" => "purchase-order-status",
                "action" => "status",
            ],
            [
                "pattern" => "/purchase-orders/:id/receive/form-context",
                "name" => "po-receive-form-context",
                "action" => "receiveFormContext",
            ],
        ],
        "purchasereceipts" => [
            [
                "pattern" => "/purchase-receipts",
                "name" => "purchase-receipts",
                "action" => "index",
            ],
        ]
        /* End - Purchasing module */
    ]    
];