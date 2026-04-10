<?php
/**
 * Sample
 return [
    "module" => "app", // it must match with the module name in /config/modules.php
    "prefix" => "app", // when its added make sure to not add that in pattern
    "routes" => [
        "front" => [
            [
                "pattern" => "/",
                "action" => "home", // must be lowercase
                "name" => "homepage", // optional
                "skipPrefix" => true, // optional, remove if need to add prefix by default
            ],
            [
                "pattern" => "/about-us",
                "action" => "aboutus", // must be lowercase                
            ],
        ]
    ]
];
* */
return [
    "module" => "app",
    "prefix" => "",
    "routes" => [
        "front" => [
            [
                "pattern" => "/",
                "action" => "home",
            ],
            [
                "pattern" => "/about-us",
                "action" => "aboutus",
            ],
            [
                "pattern" => "/contact-us",
                "action" => "contactus",
            ],
        ],
        "auth" => [
            [
                "pattern" => "/login",
                "action" => "login",
            ],
            [
                "pattern" => "/register",
                "action" => "register",
            ],
            [
                "pattern" => "/forgot-password",
                "action" => "forgotpassword",
            ],
            [
                "pattern" => "/reset-password",
                "action" => "resetpassword",
            ],
        ],
        /*"productmasters" => [
            [
                "pattern" => "/product-masters",
                "action" => "index",
            ],
        ],*/
        "products" => [
            [
                "pattern" => "/products",
                "action" => "index",
            ],
        ],
        "prodcategories" => [
            [
                "pattern" => "/product-categories",
                "action" => "index",
            ],
        ],
        "invsettings" => [
            [
                "pattern" => "/settings/inventory",
                "action" => "index",
            ]
        ],
        "locations" => [
            [
                "pattern" => "/company/locations",
                "action" => "index",
            ],
        ],
        /* Start - Inventory module */
        "inventory" => [
            [
                "pattern" => "/inv/adjustments",
                "name" => "inv-adjustments",
                "action" => "adjustments",
            ]
        ],
        "invproducts" => [
            [
                "pattern" => "/inv/products/:id/stock-locations",
                "name" => "prod-stock-locations",
                "action" => "stockLocations",
            ]
        ],
        /* End - Inventory module */

        /* Start - Sales module */
        "quotations" => [
            [
                "pattern" => "/quotations",
                "name"    => "quotations",
                "action"  => "index",
            ],
        ],
        "salesorders" => [
            [
                "pattern" => "/sales-orders",
                "name"    => "sales-orders",
                "action"  => "index",
            ],
            [
                "pattern" => "/sales-orders/:id",
                "name"    => "single-sales-order",
                "action"  => "edit",
            ],
        ],
        "salesdeliveries" => [
            [
                "pattern" => "/sales-deliveries",
                "name"    => "sales-deliveries",
                "action"  => "index",
            ],
            [
                "pattern" => "/sales-deliveries/:id",
                "name"    => "single-sales-delivery",
                "action"  => "edit",
            ],
        ],
        "customers" => [
            [
                "pattern" => "/customers",
                "name"    => "customers",
                "action"  => "index",
            ],
        ],
        /* End - Sales module */

        /* Start - Purchasing module */
        "vendors" => [
            [
                "pattern" => "/vendors",
                "name" => "vendors",
                "action" => "index",
            ]
        ],
        "purchaseorders" => [
            [
                "pattern" => "/purchase-orders",
                "name" => "purchase-orders",
                "action" => "index",
            ],
            [
                "pattern" => "/purchase-orders/:id",
                "name" => "single-purchase-order",
                "action" => "edit",
            ],
        ],
        "purchasereceipts" => [
            [
                "pattern" => "/purchase-receipts",
                "name" => "purchase-receipts",
                "action" => "index",
            ],
            [
                "pattern" => "/purchase-receipts/:id",
                "name" => "single-purchase-receipt",
                "action" => "edit",
            ],
        ],
        /* End - Purchasing module */

        "attachments" => [
            [
                "pattern" => "/attachments/:id",
                "name"    => "attachment-download",
                "action"  => "download",
            ],
        ],

        /* Start - CRM module */
        "crmleads" => [
            [
                "pattern" => "/crm/leads",
                "name"    => "crm-leads",
                "action"  => "index",
            ],
            [
                "pattern" => "/crm/pipeline",
                "name"    => "crm-leads-pipeline",
                "action"  => "pipeline",
            ],
            [
                "pattern" => "/crm/leads/:id",
                "name"    => "single-crm-lead",
                "action"  => "edit",
            ],
        ],
        "crmstages" => [
            [
                "pattern" => "/crm/stages",
                "name"    => "crm-stages",
                "action"  => "index",
            ],
        ],
        /* End - CRM module */

        /*"dashboard" => [
            [
                "pattern" => "/dashboard",
                "action" => "dashboard", // must be lowercase
                "name" => "dashboardpage", // optional
                "skipPrefix" => true, // optional, remove if need to add prefix by default
            ],
        ]*/
    ]    
];