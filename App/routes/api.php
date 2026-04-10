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
        "inventory" => [
            [
                "pattern" => "/inv/adjustments",
                "name" => "inv-adjustments",
                "action" => "adjustments",
            ],
        ],
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
        "invsequence" => [
            [
                "pattern" => "/inv/sequence/generate",
                "action" => "generate",
            ],
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
                "pattern" => "/sales-orders/form-context",
                "name"    => "so-form-context",
                "action"  => "formContext",
            ],
            [
                "pattern" => "/sales-orders/customers/search",
                "name"    => "so-customers-search",
                "action"  => "customersSearch",
            ],
            [
                "pattern" => "/sales-orders/:id",
                "name"    => "single-sales-order",
                "action"  => "entity",
            ],
            [
                "pattern" => "/sales-orders/:id/status",
                "name"    => "sales-order-status",
                "action"  => "status",
            ],
            [
                "pattern" => "/sales-orders/:id/history",
                "name"    => "sales-order-history",
                "action"  => "history",
            ],
        ],
        "salesdeliveries" => [
            [
                "pattern" => "/sales-deliveries",
                "name"    => "sales-deliveries",
                "action"  => "index",
            ],
            [
                "pattern" => "/sales-deliveries/form-context",
                "name"    => "dn-form-context",
                "action"  => "formContext",
            ],
            [
                "pattern" => "/sales-deliveries/so-search",
                "name"    => "dn-so-search",
                "action"  => "soSearch",
            ],
            [
                "pattern" => "/sales-deliveries/:id",
                "name"    => "single-sales-delivery",
                "action"  => "entity",
            ],
            [
                "pattern" => "/sales-deliveries/:id/status",
                "name"    => "sales-delivery-status",
                "action"  => "status",
            ],
            [
                "pattern" => "/sales-deliveries/:id/history",
                "name"    => "sales-delivery-history",
                "action"  => "history",
            ],
        ],
        "customers" => [
            [
                "pattern" => "/customers",
                "name"    => "customers",
                "action"  => "index",
            ],
            [
                "pattern" => "/customers/form-context",
                "name"    => "customers-form-context",
                "action"  => "formContext",
            ],
            [
                "pattern" => "/customers/check-duplicate",
                "name"    => "customers-check-duplicate",
                "action"  => "checkDuplicate",
            ],
            [
                "pattern" => "/customers/search",
                "name"    => "customers-search",
                "action"  => "search",
            ],
            [
                "pattern" => "/customers/:id",
                "name"    => "single-customer",
                "action"  => "index",
            ],
            [
                "pattern" => "/customers/:id/addresses",
                "name"    => "customer-store-address",
                "action"  => "storeAddress",
            ],
        ],
        /* End - Sales module */

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
                "pattern" => "/vendors/check-duplicate",
                "name"    => "vendors-check-duplicate",
                "action"  => "checkDuplicate",
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
            [
                "pattern" => "/purchase-orders/:id/history",
                "name" => "purchase-order-history",
                "action" => "history",
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
                "action" => "entity",
            ],
            [
                "pattern" => "/purchase-receipts/:id/form-context",
                "name" => "receipt-edit-form-context",
                "action" => "formContext",
            ],
            [
                "pattern" => "/purchase-receipts/:id/status",
                "name" => "purchase-receipt-status",
                "action" => "status",
            ],
            [
                "pattern" => "/purchase-receipts/:id/history",
                "name" => "purchase-receipt-history",
                "action" => "history",
            ],
        ],
        /* End - Purchasing module */

        /* Start - CRM module */
        "crmleads" => [
            [
                "pattern" => "/crm/leads",
                "name"    => "crm-leads",
                "action"  => "index",
            ],
            [
                "pattern" => "/crm/leads/form-context",
                "name"    => "crm-leads-form-context",
                "action"  => "formContext",
            ],
            [
                "pattern" => "/crm/leads/pipeline",
                "name"    => "crm-leads-pipeline",
                "action"  => "pipeline",
            ],
            [
                "pattern" => "/crm/leads/reorder",
                "name"    => "crm-leads-reorder",
                "action"  => "reorder",
            ],
            [
                "pattern" => "/crm/leads/:id",
                "name"    => "single-crm-lead",
                "action"  => "entity",
            ],
            [
                "pattern" => "/crm/leads/:id/status",
                "name"    => "crm-lead-status",
                "action"  => "status",
            ],
            [
                "pattern" => "/crm/leads/:id/stage",
                "name"    => "crm-lead-stage",
                "action"  => "stage",
            ],
            [
                "pattern" => "/crm/leads/:id/note",
                "name"    => "crm-lead-note",
                "action"  => "note",
            ],
            [
                "pattern" => "/crm/leads/:id/history",
                "name"    => "crm-lead-history",
                "action"  => "history",
            ],
            [
                "pattern" => "/crm/leads/:id/convert-context",
                "name"    => "crm-lead-convert-context",
                "action"  => "convertContext",
            ],
            [
                "pattern" => "/crm/leads/:id/convert",
                "name"    => "crm-lead-convert",
                "action"  => "convert",
            ],
        ],
        "crmstages" => [
            [
                "pattern" => "/crm/stages",
                "name"    => "crm-stages",
                "action"  => "index",
            ],
            [
                "pattern" => "/crm/stages/form-context",
                "name"    => "crm-stages-form-context",
                "action"  => "formContext",
            ],
            [
                "pattern" => "/crm/stages/:id",
                "name"    => "single-crm-stage",
                "action"  => "index",
            ],
        ],
        /* End - CRM module */

        /* Start - Activities */
        "activities" => [
            [
                "pattern" => "/activities",
                "name"    => "activities",
                "action"  => "index",
            ],
            [
                "pattern" => "/activities/form-context",
                "name"    => "activities-form-context",
                "action"  => "formContext",
            ],
            [
                "pattern" => "/activities/:id/done",
                "name"    => "activity-done",
                "action"  => "done",
            ],
            [
                "pattern" => "/activities/:id",
                "name"    => "single-activity",
                "action"  => "entity",
            ],
        ],
        /* End - Activities */
    ]    
];