<?php
return [
    "module" => "api",
    "prefix" => "api",
    "routes" => [
        "auth" => [
            [
                "pattern" => "/auth/login",
                "action" => "login",
                "methods" => ["POST"],
            ],
            [
                "pattern" => "/auth/refresh-token",
                "action" => "refreshToken",
                "access_key" => "refresh.token",
                "methods" => ["POST"],
            ],
            [
                "pattern" => "/auth/logout",
                "action" => "logout",
                "access_key" => "logout",
                "methods" => ["POST"],
            ],
        ],
        "companies" => [
            [
                "pattern" => "/companies/register",
                "action"  => "register",
                "methods" => ["POST"],
            ],
            [
                "pattern" => "/companies/activate",
                "action"  => "activate",
                "methods" => ["POST"],
            ],
            [
                "pattern" => "/company/profile",
                "name"    => "company-profile",
                "action"  => "profile",
                "access_key" => "company.profile.view.edit",
                "methods" => ["GET", "POST"],
            ],            
        ],
        "prodcategories" => [
            [
                "pattern" => "/products/categories",
                "action" => "index",
                "access_key" => "products.categories",
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/products/categories/form-context",
                "action" => "formContext",
                "access_key" => "products.category.formcontext",
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/products/categories/:id",
                "name" => "single-category",
                "action" => "index",
                "access_key" => "products.category.edit",
                "methods" => ["POST", "DELETE"],
            ]
        ],
        "productmasters" => [
            [
                "pattern" => "/product-masters",
                "action" => "index",
				"methods" => ["GET", "DELETE"],
            ],
        ],
        "products" => [
            [
                "pattern" => "/products",
                "action" => "index",
                "access_key" => "products",
                "methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/products/form-context",
                "action" => "formContext",
                "access_key" => "product.formcontext",
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/products/:id",
                "name" => "single-product",
                "action" => "index",
                "access_key" => "product.edit",
                "methods" => ["POST", "DELETE"],
            ],
        ],        
        "locations" => [
            [
                "pattern" => "/company/locations",
                "action" => "index",
                "access_key" => "company.locations",
				"methods" => ["GET"],
            ],
            [
                "pattern" => "/company/locations/form-context",
                "action" => "formContext",
                "access_key" => "company.location.formcontext",
				"methods" => ["GET"],
            ],
            [
                "pattern" => "/company/locations/:id",
                "name" => "single-location",
                "action" => "index",
                "access_key" => "company.location.view.edit",
				"methods" => ["POST", "DELETE"],
            ]
        ],
        /* Start - Inventory module */
        "inventory" => [
            [
                "pattern" => "/inv/adjustments",
                "name" => "inv-adjustments",
                "action" => "adjustments",
                "access_key" => "inv.adjustments",
				"methods" => ["GET"],
            ],
        ],
        "invproducts" => [
            [
                "pattern" => "/inv/products/:id/stock-locations", // :id is product id
                "name" => "prod-stock-locations",
                "action" => "stockLocations",
                "access_key" => "inv.stocklocation",
				"methods" => ["GET"],				
            ],
            [
                "pattern" => "/inv/products/:id/stock/adjust", // :id is product id
                "name" => "prod-adjust-stock",
                "action" => "adjustStock",
                "access_key" => "inv.stock.adjust.save",
				"methods" => ["POST"],
            ],
            [
                "pattern" => "/inv/products/:id/stock/adjust/form-context", // :id is product id
                "name" => "prod-add-edit-stock-location",
                "action" => "adjustFormContext",
                "access_key" => "inv.stock.adjust.formcontext",
				"methods" => ["GET"],
            ],
            [
                "pattern" => "/inv/products/:id/serial-or-lot-numbers", // :id is product id
                "name" => "prod-serial-or-lot-numbers",
                "action" => "serialOrLotNumbers",
                "access_key" => "inv.stock.serial.lot.numbers",
				"methods" => ["GET"],
            ],

        ],
        "invsequence" => [
            [
                "pattern" => "/inv/sequence/generate",
                "action" => "generate",
				"methods" => ["POST"],
            ],
        ],
        /* End - Inventory module */

        /* Start - Sales module */
        "quotations" => [
            [
                "pattern" => "/sales/quotations",
                "name"    => "quotations",
                "action"  => "index",
                "access_key" => "sales.quotations",
				"methods" => ["GET"],
            ],
        ],
        "salesorders" => [
            [
                "pattern" => "/sales/orders",
                "name"    => "sales-orders",
                "action"  => "index",
                "access_key" => "sales.orders",
				"methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/sales/orders/form-context",
                "name"    => "so-form-context",
                "action"  => "formContext",
                "access_key" => "sales.order.formcontext",
				"methods" => ["GET"],
            ],
            [
                "pattern" => "/sales/orders/customers/search",
                "name"    => "so-customers-search",
                "action"  => "customersSearch",
                "access_key" => "sales.orders.customer.search",
				"methods" => ["GET"],
            ],
            [
                "pattern" => "/sales/orders/:id",
                "name"    => "single-sales-order",
                "action"  => "entity",
                "access_key" => "sales.orders.entity",
				"methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/sales/orders/:id/status",
                "name"    => "sales-order-status",
                "action"  => "status",
                "access_key" => "sales.order.status",
				"methods" => ["POST"],
            ],
            [
                "pattern" => "/sales/orders/:id/history",
                "name"    => "sales-order-history",
                "action"  => "history",
                "access_key" => "sales.order.history",
				"methods" => ["GET"],
            ],
            [
                "pattern" => "/sales/orders/:id/send-email",
                "name"    => "so-send-email",
                "action"  => "sendEmail",
                "access_key" => "sales.order.send.email",
				"methods" => ["POST"],
            ],
        ],
        "salesdeliveries" => [
            [
                "pattern" => "/sales/deliveries",
                "name"    => "sales-deliveries",
                "action"  => "index",
                "access_key" => "sales.deliveries",
				"methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/sales/deliveries/form-context",
                "name"    => "dn-form-context",
                "action"  => "formContext",
                "access_key" => "sales.delivery.formcontext",
				"methods" => ["GET"],
            ],
            [
                "pattern" => "/sales/deliveries/so-search",
                "name"    => "dn-so-search",
                "action"  => "soSearch",
                "access_key" => "sales.delivery.sosearch",
				"methods" => ["GET"],
            ],
            [
                "pattern" => "/sales/deliveries/:id",
                "name"    => "single-sales-delivery",
                "action"  => "entity",
                "access_key" => "sales.delivery.entity",
				"methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/sales/deliveries/:id/status",
                "name"    => "sales-delivery-status",
                "action"  => "status",
                "access_key" => "sales.delivery.status",
				"methods" => ["POST"],
            ],
            [
                "pattern" => "/sales/deliveries/:id/history",
                "name"    => "sales-delivery-history",
                "action"  => "history",
                "access_key" => "sales.delivery.history",
				"methods" => ["GET"],
            ],
        ],        
        "customers" => [
            [
                "pattern" => "/customers",
                "name"    => "customers",
                "action"  => "index",
                "access_key" => "customers",
				"methods" => ["GET"],
            ],
            [
                "pattern" => "/customers/form-context",
                "name"    => "customers-form-context",
                "action"  => "formContext",
                "access_key" => "customer.formcontext",
				"methods" => ["GET"],
            ],
            [
                "pattern" => "/customers/check-duplicate",
                "name"    => "customers-check-duplicate",
                "action"  => "checkDuplicate",
                "access_key" => "customer.check.duplicate",
				"methods" => ["GET"],
            ],
            [
                "pattern" => "/customers/search",
                "name"    => "customers-search",
                "action"  => "search",
                "access_key" => "customer.search",
				"methods" => ["GET"],
            ],
            [
                "pattern" => "/customers/:id",
                "name"    => "single-customer",
                "action"  => "index",
                "access_key" => "customer.edit",
				"methods" => ["POST"],
            ],
            [
                "pattern" => "/customers/:id/addresses",
                "name"    => "customer-store-address",
                "action"  => "storeAddress",
                "access_key" => "customer.address",
				"methods" => ["POST"],
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
				"methods" => ["GET"],
            ],
            [
                "pattern" => "/vendors/form-context",
                "name" => "vendors-form-context",
                "action" => "formContext",
                "access_key" => "vendor.formcontext",
				"methods" => ["GET"],
            ],
            [
                "pattern" => "/vendors/check-duplicate",
                "name"    => "vendors-check-duplicate",
                "action"  => "checkDuplicate",
                "access_key" => "vendors.check.duplicate",
				"methods" => ["GET"],
            ],
            [
                "pattern" => "/vendors/:id",
                "name" => "single-vendor",
                "action" => "index",
                "access_key" => "vendor.edit",
				"methods" => ["POST"],
            ],
        ],
        "purchaseorders" => [
            [
                "pattern" => "/purchase/orders",
                "name" => "purchase-orders",
                "action" => "index",
                "access_key" => "purchase.orders",
				"methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/purchase/orders/form-context",
                "name" => "po-form-context",
                "action" => "formContext",
                "access_key" => "purchase.order.formcontext",
				"methods" => ["GET"],
            ],
            [
                "pattern" => "/purchase/orders/:id",
                "name" => "single-purchase-order",
                "action" => "entity",
                "access_key" => "purchase.order.view.edit",
				"methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/purchase/orders/:id/status",
                "name" => "purchase-order-status",
                "action" => "status",
                "access_key" => "purchase.order.status",
				"methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/purchase/orders/:id/receive/form-context",
                "name" => "po-receive-form-context",
                "action" => "receiveFormContext",
                "access_key" => "purchase.order.receive.formcontext",
				"methods" => ["GET"],
            ],
            [
                "pattern" => "/purchase/orders/:id/history",
                "name" => "purchase-order-history",
                "action" => "history",
                "access_key" => "purchase.order.history",
				"methods" => ["GET"],
            ],
        ],
        "purchasereceipts" => [
            [
                "pattern" => "/purchase/receipts",
                "name" => "purchase-receipts",
                "action" => "index",
                "access_key" => "purchase.receipts",
				"methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/purchase/receipts/:id",
                "name" => "single-purchase-receipt",
                "action" => "entity",
                "access_key" => "purchase.receipt.view.edit",
				"methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/purchase/receipts/:id/form-context",
                "name" => "receipt-edit-form-context",
                "action" => "formContext",
                "access_key" => "purchase.receipt.formcontext",
				"methods" => ["GET"],
            ],
            [
                "pattern" => "/purchase/receipts/:id/status",
                "name" => "purchase-receipt-status",
                "action" => "status",
                "access_key" => "purchase.receipt.status",
				"methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/purchase/receipts/:id/history",
                "name" => "purchase-receipt-history",
                "action" => "history",
                "access_key" => "purchase.receipt.history",
				"methods" => ["GET"],
            ],
        ],
        /* End - Purchasing module */

        /* Start - Subscription */
        "subscriptions" => [
            [
                "pattern" => "/subscription/summary",
                "name"    => "subscription-summary",
                "action"  => "summary",
                "access_key" => "subscription.summary",
				"methods" => ["GET"],
            ],
            [
                "pattern" => "/subscription/module",
                "name"    => "subscription-module",
                "action"  => "module",
                "access_key" => "subscription.module",
				"methods" => ["POST"],
            ],
            [
                "pattern" => "/subscription/upgrade",
                "name"    => "subscription-upgrade",
                "action"  => "upgrade",
                "access_key" => "subscription.upgrade",
				"methods" => ["POST"],
            ],
            [
                "pattern" => "/subscription/downgrade",
                "name"    => "subscription-downgrade",
                "action"  => "downgrade",
                "access_key" => "subscription.downgrade",
				"methods" => ["POST"],
            ],
            [
                "pattern" => "/subscription/cancel",
                "name"    => "subscription-cancel",
                "action"  => "cancel",
                "access_key" => "subscription.cancel",
				"methods" => ["POST"],
            ],
        ],
        /* End - Subscription */

        /* Start - Users */
        "users" => [
            [
                "pattern" => "/users",
                "action"  => "index",
                "access_key" => "company.users",
				"methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/users/me",
                "action"  => "me",
                "access_key" => "company.user.me",
				"methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/users/me/password",
                "action"  => "mePassword",
                "access_key" => "company.user.password",
				"methods" => ["POST"],
            ],
            [
                "pattern" => "/users/form-context",
                "action"  => "formContext",
                "access_key" => "company.user.formcontext",
				"methods" => ["GET"],
            ],
            [
                "pattern" => "/users/roles",
                "action"  => "roles",
                "access_key" => "company.users.roles",
				"methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/users/roles/form-context",
                "action"  => "rolesFormContext",
                "access_key" => "company.user.role.formcontext",
				"methods" => ["GET"],
            ],
            [
                "pattern" => "/users/roles/:id/permissions",
                "name" => "role-permissions",
                "action" => "rolesPermissions",
                "access_key" => "company.user.roles.permissions",
				"methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/users/roles/:id",
                "name"    => "single-role",
                "action"  => "rolesEntity",
                "access_key" => "company.users.roles.view.edit",
				"methods" => ["POST"],
            ],
            [
                "pattern" => "/users/:id/status",
                "name"    => "user-status",
                "action"  => "status",
                "access_key" => "user.status",
				"methods" => ["POST"],
            ],
            [
                "pattern" => "/users/:id",
                "name"    => "single-user",
                "action"  => "entity",
                "access_key" => "user.view.edit",
				"methods" => ["POST"],
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
				"methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/crm/leads/form-context",
                "name"    => "crm-leads-form-context",
                "action"  => "formContext",
                "access_key" => "crm.leads.formcontext",
				"methods" => ["GET"],
            ],
            [
                "pattern" => "/crm/leads/pipeline",
                "name"    => "crm-leads-pipeline",
                "action"  => "pipeline",
                "access_key" => "crm.leads.pipeline",
				"methods" => ["GET"],
            ],
            [
                "pattern" => "/crm/leads/reorder",
                "name"    => "crm-leads-reorder",
                "action"  => "reorder",
                "access_key" => "crm.leads.reorder",
				"methods" => ["POST"],
            ],
            [
                "pattern" => "/crm/leads/:id",
                "name"    => "single-crm-lead",
                "action"  => "entity",
                "access_key" => "crm.leads.entity",
				"methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/crm/leads/:id/status",
                "name"    => "crm-lead-status",
                "action"  => "status",
                "access_key" => "crm.leads.status",
				"methods" => ["POST"],
            ],
            [
                "pattern" => "/crm/leads/:id/stage",
                "name"    => "crm-lead-stage",
                "action"  => "stage",
                "access_key" => "crm.leads.stage",
				"methods" => ["POST"],
            ],
            [
                "pattern" => "/crm/leads/:id/note",
                "name"    => "crm-lead-note",
                "action"  => "note",
                "access_key" => "crm.leads.note",
				"methods" => ["POST"],
            ],
            [
                "pattern" => "/crm/leads/:id/history",
                "name"    => "crm-lead-history",
                "action"  => "history",
                "access_key" => "crm.leads.history",
				"methods" => ["GET"],
            ],
            [
                "pattern" => "/crm/leads/:id/convert-context",
                "name"    => "crm-lead-convert-context",
                "action"  => "convertContext",
                "access_key" => "crm.leads.convert.formcontext",
				"methods" => ["GET"],
            ],
            [
                "pattern" => "/crm/leads/:id/convert",
                "name"    => "crm-lead-convert",
                "action"  => "convert",
                "access_key" => "crm.leads.convert",
				"methods" => ["POST"],
            ],
        ],
        "crmintegrations" => [
            [
                "pattern" => "/crm/integrations",
                "name"    => "crm-integrations",
                "action"  => "index",
                "access_key" => "crm.integrations",
				"methods" => ["GET", "POST", "DELETE"],
				
            ],
            [
                "pattern" => "/crm/integrations/form-context",
                "name"    => "crm-integrations-form-context",
                "action"  => "formContext",
                "access_key" => "crm.integration.formcontext",
				"methods" => ["GET"],
            ],
            [
                "pattern" => "/crm/integrations/:id",
                "name"    => "single-crm-integration",
                "action"  => "entity",
                "access_key" => "crm.integration.view.edit",
				"methods" => ["POST"],
            ],
        ],
        "crmstages" => [
            [
                "pattern" => "/crm/stages",
                "name"    => "crm-stages",
                "action"  => "index",
                "access_key" => "crm.stages",
				"methods" => ["GET"],
            ],
            [
                "pattern" => "/crm/stages/form-context",
                "name"    => "crm-stages-form-context",
                "action"  => "formContext",
                "access_key" => "crm.stage.formcontext",
				"methods" => ["GET"],
            ],
            [
                "pattern" => "/crm/stages/:id",
                "name"    => "single-crm-stage",
                "action"  => "index",
                "access_key" => "crm.stage.view.edit",
				"methods" => ["POST", "DELETE"],
            ],
        ],
        /* End - CRM module */

        /* Start - Webhooks */
        "webhooks" => [
            [
                "pattern" => "/webhooks/:source/:token",
                "name"    => "webhook-receive",
                "action"  => "receive",
				"methods" => ["POST"],
            ],
        ],
        /* End - Webhooks */

        /* Start - Dashboard */
        "dashboard" => [
            [
                "pattern" => "/dashboard/summary",
                "name"    => "dashboard-summary",
                "action"  => "summary",
                "access_key" => "dashboard.summary",
                "methods" => ["GET"],
            ],
        ],
        /* End - Dashboard */

        /* Start - Activities */
        "activities" => [
            [
                "pattern" => "/activities",
                "name"    => "activities",
                "action"  => "index",
                "access_key" => "activities",
				"methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/activities/form-context",
                "name"    => "activities-form-context",
                "action"  => "formContext",
                "access_key" => "activity.formcontext",
				"methods" => ["GET"],
            ],
            [
                "pattern" => "/activities/:id/done",
                "name"    => "activity-done",
                "action"  => "done",
                "access_key" => "activity.done",
				"methods" => ["POST"],
            ],
            [
                "pattern" => "/activities/:id",
                "name"    => "single-activity",
                "action"  => "entity",
                "access_key" => "activity.view.edit",
				"methods" => ["POST", "DELETE"],
            ],
        ],
        /* End - Activities */
    ]    
];