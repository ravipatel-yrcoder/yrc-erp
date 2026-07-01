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
                "access_keys" => ["dashboard"],
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
                "access_keys" => ["company_settings"],
            ],
            [
                "pattern" => "/settings/accounting",
                "action"  => "accounting",
                "access_keys" => ["company_settings"],
            ],
        ],
        "products" => [
            [
                "pattern" => "/products",
                "action" => "index",
                "access_keys" => ["products"],
            ],
        ],
        "prodcategories" => [
            [
                "pattern" => "/products/categories",
                "action" => "index",
                "access_keys" => ["product_categories"],
            ],
        ],
        "invsettings" => [
            [
                "pattern" => "/settings/inventory",
                "action" => "index",
                "access_keys" => ["company_settings"],
            ]
        ],
        "doctemplates" => [
            [
                "pattern"     => "/settings/doc-templates",
                "name"        => "doc-templates",
                "action"      => "index",
                "access_keys" => ["company_settings"],
            ],
        ],
        "docsequences" => [
            [
                "pattern"     => "/settings/doc-sequences",
                "name"        => "doc-sequences",
                "action"      => "index",
                "access_keys" => ["company_settings"],
            ],
        ],
        "locations" => [
            [
                "pattern" => "/company/locations",
                "action" => "index",
                "access_keys" => ["company_locations"],
            ],
        ],
        /* Start - Inventory module */
        "inventory" => [
            [
                "pattern"    => "/inv/items",
                "name"       => "inv-items",
                "action"     => "items",
                "access_keys" => ["inventory_items"],
            ],
            [
                "pattern" => "/inv/adjustments",
                "name" => "inv-adjustments",
                "action" => "adjustments",
                "access_keys" => ["inventory_adjustments"],
            ],
            [
                "pattern" => "/inv/movements",
                "name" => "inv-movements",
                "action" => "movements",
                "access_keys" => ["inventory_movements"],
            ],
        ],
        "invproducts" => [
            [
                "pattern" => "/inv/products/:id/stock-locations",
                "name" => "prod-stock-locations",
                "action" => "stockLocations",
                "access_keys" => ["inventory_items"],
            ]
        ],
        /* End - Inventory module */

        /* Start - Sales module */
        "salesorders" => [
            [
                "pattern" => "/sales/quotations",
                "name"    => "quotations",
                "action"  => "quotations",
                "access_keys" => ["sales_orders"],
            ],
            [
                "pattern" => "/sales/orders",
                "name"    => "sales-orders",
                "action"  => "index",
                "access_keys" => ["sales_orders"],
            ],
            [
                "pattern" => "/sales/orders/:id",
                "name"    => "single-sales-order",
                "action"  => "edit",
                "access_keys" => ["sales_orders"],
            ],
            [
                "pattern" => "/sales/orders/:id/pdf",
                "name"    => "so-pdf",
                "action"  => "pdf",
                "access_keys" => ["sales_orders"],
            ],
        ],
        "salesdeliveries" => [
            [
                "pattern" => "/sales/deliveries",
                "name"    => "sales-deliveries",
                "action"  => "index",
                "access_keys" => ["sales_deliveries"],
            ],
            [
                "pattern" => "/sales/deliveries/:id",
                "name"    => "single-sales-delivery",
                "action"  => "edit",
                "access_keys" => ["sales_deliveries"],
            ],
        ],
        "salesreturns" => [
            [
                "pattern"     => "/sales/returns",
                "name"        => "returns",
                "action"      => "index",
                "access_keys" => ["sales_returns"],
            ],
            [
                "pattern"     => "/sales/returns/:id",
                "name"        => "return-detail",
                "action"      => "edit",
                "access_keys" => ["sales_returns"],
            ],
        ],        
        "customers" => [
            [
                "pattern" => "/customers",
                "name"    => "customers",
                "action"  => "index",
                "access_keys" => ["customers"],
            ],
        ],
        /* End - Sales module */

        /* Start - Purchasing module */
        "vendors" => [
            [
                "pattern" => "/vendors",
                "name" => "vendors",
                "action" => "index",
                "access_keys" => ["vendors"],
            ]
        ],
        "purchaseorders" => [
            [
                "pattern" => "/purchase/orders",
                "name" => "purchase-orders",
                "action" => "index",
                "access_keys" => ["purchase_orders"],
            ],
            [
                "pattern" => "/purchase/orders/:id",
                "name" => "single-purchase-order",
                "action" => "edit",
                "access_keys" => ["purchase_orders"],
            ],
            [
                "pattern" => "/purchase/orders/:id/pdf",
                "name" => "po-pdf",
                "action" => "pdf",
                "access_keys" => ["purchase_orders"],
            ],
        ],
        "purchasereceipts" => [
            [
                "pattern" => "/purchase/receipts",
                "name" => "purchase-receipts",
                "action" => "index",
                "access_keys" => ["purchase_receipts"],
            ],
            [
                "pattern" => "/purchase/receipts/:id",
                "name" => "single-purchase-receipt",
                "action" => "edit",
                "access_keys" => ["purchase_receipts"],
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

        /* Start - Subscription */
        "subscriptions" => [
            [
                "pattern" => "/settings/subscription",
                "action"  => "index",
                "access_keys" => ["company_subscription"],
            ],
        ],
        "billing" => [
            [
                "pattern" => "/settings/billing",
                "action"  => "index",
                "access_keys" => ["company_subscription"],
            ],
        ],
        "subscriptionexpired" => [
            [
                "pattern" => "/subscription/expired",
                "action"  => "index",
            ],
        ],
        /* End - Subscription */

        /* Start - Users */
        "users" => [
            [
                "pattern" => "/company/users",
                "action"  => "index",
                "access_keys" => ["company_users"],
            ],
            [
                "pattern" => "/company/users/roles",
                "action"  => "roles",
                "access_keys" => ["company_roles_mgmt"],
            ],
        ],
        /* End - Users */

        /* Start - Teams */
        "teams" => [
            [
                "pattern" => "/company/teams",
                "action"  => "index",
                "access_keys" => ["company_teams"],
            ],
        ],
        /* End - Teams */

        /* Start - Activities */
        "activities" => [
            [
                "pattern"     => "/activities",
                "action"      => "index",
                "access_keys" => ["activities"],
            ],
        ],
        /* End - Activities */

        /* Start - CRM module */
        "crmleads" => [
            [
                "pattern" => "/crm/leads",
                "name"    => "crm-leads",
                "action"  => "index",
                "access_keys" => ["crm_leads"],
            ],
            [
                "pattern" => "/crm/pipeline",
                "name"    => "crm-leads-pipeline",
                "action"  => "pipeline",
                "access_keys" => ["crm_leads"],
            ],
            [
                "pattern" => "/crm/leads/:id",
                "name"    => "single-crm-lead",
                "action"  => "edit",
                "access_keys" => ["crm_leads"],
            ],
        ],
        "crmstages" => [
            [
                "pattern" => "/crm/stages",
                "name"    => "crm-stages",
                "action"  => "index",
                "access_keys" => ["crm_stages"],
            ],
        ],
        "crmintegrations" => [
            [
                "pattern" => "/crm/integrations",
                "name"    => "crm-integrations",
                "action"  => "index",
                "access_keys" => ["crm_integrations"],
            ],
        ],
        /* End - CRM module */        

        /* Start - Manufacturing module */
        "manufacturingboms" => [
            [
                "pattern"     => "/manufacturing/boms",
                "name"        => "manufacturing-boms",
                "action"      => "index",
                "access_keys" => ["manufacturing_boms"],
            ],
        ],
        "manufacturingorders" => [
            [
                "pattern"     => "/manufacturing/orders",
                "name"        => "manufacturing-orders",
                "action"      => "index",
                "access_keys" => ["manufacturing_orders"],
            ],
            [
                "pattern"     => "/manufacturing/orders/:id",
                "name"        => "manufacturing-order-detail",
                "action"      => "edit",
                "access_keys" => ["manufacturing_orders"],
            ],
            [
                "pattern"     => "/manufacturing/orders/:id/material-requirement-sheet",
                "name"        => "manufacturing-order-mrs",
                "action"      => "materialRequirementSheet",
                "access_keys" => ["manufacturing_orders"],
            ],
            [
                "pattern"     => "/manufacturing/orders/:id/allocations/:allocId/issue-slip",
                "name"        => "manufacturing-order-issue-slip",
                "action"      => "issueSlip",
                "access_keys" => ["manufacturing_orders"],
            ],
        ],
        /* End - Manufacturing module */

        /* Start - Reporting */
        "reportprofitmargin" => [
            [
                "pattern"     => "/reports/profit-margin",
                "name"        => "report-profit-margin",
                "action"      => "lineItems",
                "access_keys" => ["reporting_profit_margin"],
            ],
        ],
        /* End - Reporting */
    ]
];
