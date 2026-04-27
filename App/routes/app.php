<?php
/**
 * Sample
 return [
    "module" => "app", // it must match with the module name in /config/modules.php
    "prefix" => "app", // when its added make sure to not add that in pattern
    "disable_auto_route" => true,
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
    "disable_auto_route" => true,
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
                "pattern" => "/forgot-password",
                "action" => "forgotpassword",
            ],
            [
                "pattern" => "/reset-password",
                "action" => "resetpassword",
            ],
        ],
        "dashboard" => [
            [
                "pattern" => "/dashboard",
                "action"  => "index",
                "access_key" => "dashboard",
            ],
        ],
        "companies" => [
            [
                "pattern" => "/register",
                "action"  => "register",
            ],
            [
                "pattern" => "/companies/activate",
                "action"  => "activate",
            ],
            [
                "pattern" => "/settings/general",
                "action"  => "profile",
                "access_key" => "companies.general",
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
                "access_key" => "products",                
            ],
        ],
        "prodcategories" => [
            [
                "pattern" => "/products/categories",
                "action" => "index",
                "access_key" => "products.categories",
            ],
        ],
        "invsettings" => [
            [
                "pattern" => "/settings/inventory",
                "action" => "index",
                "access_key" => "settings.inventory",
            ]
        ],
        "locations" => [
            [
                "pattern" => "/company/locations",
                "action" => "index",
                "access_key" => "company.locations",
            ],
        ],
        /* Start - Inventory module */
        "inventory" => [
            [
                "pattern" => "/inv/adjustments",
                "name" => "inv-adjustments",
                "action" => "adjustments",
                "access_key" => "inv.adjustments",
            ]
        ],
        "invproducts" => [
            [
                "pattern" => "/inv/products/:id/stock-locations",
                "name" => "prod-stock-locations",
                "action" => "stockLocations",
                "access_key" => "inv.stocklocation",
            ]
        ],
        /* End - Inventory module */

        /* Start - Sales module */
        "salesorders" => [
            [
                "pattern" => "/sales/quotations",
                "name"    => "quotations",
                "action"  => "quotations",
                "access_key" => "sales.quotations",
            ],
            [
                "pattern" => "/sales/orders",
                "name"    => "sales-orders",
                "action"  => "index",
                "access_key" => "sales.orders",
            ],
            [
                "pattern" => "/sales/orders/:id",
                "name"    => "single-sales-order",
                "action"  => "edit",
                "access_key" => "sales.order.edit",
            ],
            [
                "pattern" => "/sales/orders/:id/pdf",
                "name"    => "so-pdf",
                "action"  => "pdf",
                "access_key" => "sales.order.pdf",
            ],
            [
                "pattern" => "/sales/orders/:id/print-view",
                "name"    => "so-print-view",
                "action"  => "printView",
                "access_key" => "sales.order.printview",
            ],
        ],
        "salesdeliveries" => [
            [
                "pattern" => "/sales/deliveries",
                "name"    => "sales-deliveries",
                "action"  => "index",
                "access_key" => "sales.deliveries",
            ],
            [
                "pattern" => "/sales/deliveries/:id",
                "name"    => "single-sales-delivery",
                "action"  => "edit",
                "access_key" => "sales.delivery.edit",
            ],
        ],
        "customers" => [
            [
                "pattern" => "/customers",
                "name"    => "customers",
                "action"  => "index",
                "access_key" => "customers",
            ],
        ],
        /* End - Sales module */

        /* Start - Purchasing module */
        "vendors" => [
            [
                "pattern" => "/vendors",
                "name" => "vendors",
                "action" => "index",
                "access_key" => "vendors",
            ]
        ],
        "purchaseorders" => [
            [
                "pattern" => "/purchase/orders",
                "name" => "purchase-orders",
                "action" => "index",
                "access_key" => "purchase.orders",
            ],
            [
                "pattern" => "/purchase/orders/:id",
                "name" => "single-purchase-order",
                "action" => "edit",
                "access_key" => "purchase.order.view.edit",
            ],
        ],
        "purchasereceipts" => [
            [
                "pattern" => "/purchase/receipts",
                "name" => "purchase-receipts",
                "action" => "index",
                "access_key" => "purchase.receipts",
            ],
            [
                "pattern" => "/purchase/receipts/:id",
                "name" => "single-purchase-receipt",
                "action" => "edit",
                "access_key" => "purchase.receipt.view.edit",
            ],
        ],
        /* End - Purchasing module */

        "attachments" => [
            [
                "pattern" => "/attachments/:id",
                "name"    => "attachment-download",
                "action"  => "download",
                "access_key" => "attachments",
            ],
        ],

        /* Start - Subscription */
        "subscriptions" => [
            [
                "pattern" => "/settings/subscription",
                "action"  => "index",
                "access_key" => "subscription",
            ],
        ],
        "billing" => [
            [
                "pattern" => "/settings/billing",
                "action"  => "index",
                "access_key" => "billing",
            ],
        ],
        "subscriptionexpired" => [
            [
                "pattern" => "/subscription/expired",
                "action"  => "index",
                "access_key" => "subscription.expired",
            ],
        ],
        /* End - Subscription */

        /* Start - Users */
        "users" => [
            [
                "pattern" => "/company/users",
                "action"  => "index",
                "access_key" => "company.users",
            ],
            [
                "pattern" => "/company/users/roles",
                "action"  => "roles",
                "access_key" => "company.users.roles",
            ],
        ],
        /* End - Users */

        /* Start - CRM module */
        "crmleads" => [
            [
                "pattern" => "/crm/leads",
                "name"    => "crm-leads",
                "action"  => "index",
                "access_key" => "crm.leads",
            ],
            [
                "pattern" => "/crm/pipeline",
                "name"    => "crm-leads-pipeline",
                "action"  => "pipeline",
                "access_key" => "crm.pipeline",
            ],
            [
                "pattern" => "/crm/leads/:id",
                "name"    => "single-crm-lead",
                "action"  => "edit",
                "access_key" => "crm.leads.edit",
            ],
        ],
        "crmstages" => [
            [
                "pattern" => "/crm/stages",
                "name"    => "crm-stages",
                "action"  => "index",
                "access_key" => "crm.stages",
            ],
        ],
        "crmintegrations" => [
            [
                "pattern" => "/crm/integrations",
                "name"    => "crm-integrations",
                "action"  => "index",
                "access_key" => "crm.integrations",
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