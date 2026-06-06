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
                "methods" => ["POST"],
            ],
            [
                "pattern" => "/auth/logout",
                "action" => "logout",
                "methods" => ["POST"],
            ],
            [
                "pattern" => "/auth/forgot-password",
                "action" => "forgotPassword",
                "methods" => ["POST"],
            ],
            [
                "pattern" => "/auth/reset-password",
                "action" => "resetPassword",
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
                "access_keys" => ["company_settings"],
                "methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/company/settings/general",
                "name"    => "company-settings-general",
                "action"  => "generalSettings",
                "access_keys" => ["company_settings"],
                "methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/company/settings/accounting",
                "name"    => "company-settings-accounting",
                "action"  => "accountingSettings",
                "access_keys" => ["company_settings"],
                "methods" => ["GET", "POST"],
            ],
            [
                "pattern"     => "/company/settings/doc-templates",
                "name"        => "company-settings-doc-templates",
                "action"      => "docTemplates",
                "access_keys" => ["company_settings"],
                "methods"     => ["GET", "POST"],
            ],
        ],
        "prodcategories" => [
            [
                "pattern" => "/products/categories",
                "action" => "index",
                "access_keys" => ["product_categories"],
                "methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/products/categories/form-context",
                "action" => "formContext",
                "access_keys" => ["product_categories"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/products/categories/:id",
                "name" => "single-category",
                "action" => "index",
                "access_keys" => ["product_categories"],
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
                "access_keys" => ["products"],
                "methods" => ["GET", "POST", "DELETE"],
            ],
            [
                "pattern" => "/products/form-context",
                "action" => "formContext",
                "access_keys" => ["products"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/products/search",
                "name"    => "products-search",
                "action"  => "search",
                "access_keys" => ["products"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/products/:id",
                "name" => "single-product",
                "action" => "index",
                "access_keys" => ["products"],
                "methods" => ["POST"],
            ],
        ],
        "locations" => [
            [
                "pattern" => "/company/locations",
                "action" => "index",
                "access_keys" => ["company_locations"],
                "methods" => ["GET", "POST", "DELETE"],
            ],
            [
                "pattern" => "/company/locations/form-context",
                "action" => "formContext",
                "access_keys" => ["company_locations"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/company/locations/:id",
                "name" => "single-location",
                "action" => "index",
                "access_keys" => ["company_locations"],
                "methods" => ["POST", "DELETE"],
            ]
        ],
        /* Start - Inventory module */
        "inventory" => [
            [
                "pattern" => "/inv/items",
                "name"    => "inv-items",
                "action"  => "items",
                "access_keys" => ["inventory_items"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/inv/adjustments",
                "name" => "inv-adjustments",
                "action" => "adjustments",
                "access_keys" => ["inventory_adjustments"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/inv/movements/form-context",
                "name" => "inv-movements-form-context",
                "action" => "movementsFormContext",
                "access_keys" => ["inventory_movements"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/inv/movements",
                "name" => "inv-movements",
                "action" => "movements",
                "access_keys" => ["inventory_movements"],
                "methods" => ["GET"],
            ],
        ],
        "invproducts" => [
            [
                "pattern" => "/inv/products/:id/stock-locations",
                "name" => "prod-stock-locations",
                "action" => "stockLocations",
                "access_keys" => ["inventory_items"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/inv/products/:id/stock/adjust",
                "name" => "prod-adjust-stock",
                "action" => "adjustStock",
                "access_keys" => ["inventory_items"],
                "methods" => ["POST"],
            ],
            [
                "pattern" => "/inv/products/:id/stock/adjust/form-context",
                "name" => "prod-add-edit-stock-location",
                "action" => "adjustFormContext",
                "access_keys" => ["inventory_items"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/inv/products/:id/serial-or-lot-numbers",
                "name" => "prod-serial-or-lot-numbers",
                "action" => "serialOrLotNumbers",
                "access_keys" => ["inventory_items"],
                "methods" => ["GET"],
            ],
        ],
        "invsequence" => [
            [
                "pattern" => "/inv/sequence/generate",
                "action" => "generate",
                "access_keys" => ["inventory_items"],
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
                "access_keys" => ["sales_orders"],
                "methods" => ["GET"],
            ],
        ],
        "salesorders" => [
            [
                "pattern" => "/sales/orders",
                "name"    => "sales-orders",
                "action"  => "index",
                "access_keys" => ["sales_orders"],
                "methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/sales/orders/form-context",
                "name"    => "so-form-context",
                "action"  => "formContext",
                "access_keys" => ["sales_orders"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/sales/orders/customers/search",
                "name"    => "so-customers-search",
                "action"  => "customersSearch",
                "access_keys" => ["sales_orders"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/sales/orders/:id",
                "name"    => "single-sales-order",
                "action"  => "entity",
                "access_keys" => ["sales_orders"],
                "methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/sales/orders/:id/status",
                "name"    => "sales-order-status",
                "action"  => "status",
                "access_keys" => ["sales_orders"],
                "methods" => ["POST"],
            ],
            [
                "pattern" => "/sales/orders/:id/history",
                "name"    => "sales-order-history",
                "action"  => "history",
                "access_keys" => ["sales_orders"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/sales/orders/:id/generate-email-pdf",
                "name"    => "so-generate-email-pdf",
                "action"  => "generateEmailPdf",
                "access_keys" => ["sales_orders"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/sales/orders/:id/send-email",
                "name"    => "so-send-email",
                "action"  => "sendEmail",
                "access_keys" => ["sales_orders"],
                "methods" => ["POST"],
            ],
        ],
        "salesdeliveries" => [
            [
                "pattern" => "/sales/deliveries",
                "name"    => "sales-deliveries",
                "action"  => "index",
                "access_keys" => ["sales_deliveries"],
                "methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/sales/deliveries/form-context",
                "name"    => "dn-form-context",
                "action"  => "formContext",
                "access_keys" => ["sales_deliveries"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/sales/deliveries/so-search",
                "name"    => "dn-so-search",
                "action"  => "soSearch",
                "access_keys" => ["sales_deliveries"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/sales/deliveries/:id",
                "name"    => "single-sales-delivery",
                "action"  => "entity",
                "access_keys" => ["sales_deliveries"],
                "methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/sales/deliveries/:id/status",
                "name"    => "sales-delivery-status",
                "action"  => "status",
                "access_keys" => ["sales_deliveries"],
                "methods" => ["POST"],
            ],
            [
                "pattern" => "/sales/deliveries/:id/history",
                "name"    => "sales-delivery-history",
                "action"  => "history",
                "access_keys" => ["sales_deliveries"],
                "methods" => ["GET"],
            ],
        ],
        "customers" => [
            [
                "pattern" => "/customers",
                "name"    => "customers",
                "action"  => "index",
                "access_keys" => ["customers"],
                "methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/customers/form-context",
                "name"    => "customers-form-context",
                "action"  => "formContext",
                "access_keys" => ["customers"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/customers/check-duplicate",
                "name"    => "customers-check-duplicate",
                "action"  => "checkDuplicate",
                "access_keys" => ["customers"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/customers/search",
                "name"    => "customers-search",
                "action"  => "search",
                "access_keys" => ["customers"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/customers/:id",
                "name"    => "single-customer",
                "action"  => "index",
                "access_keys" => ["customers"],
                "methods" => ["POST"],
            ],
            [
                "pattern" => "/customers/:id/addresses",
                "name"    => "customer-store-address",
                "action"  => "storeAddress",
                "access_keys" => ["customers"],
                "methods" => ["POST"],
            ],
            [
                "pattern" => "/customers/:id/shipping-addresses",
                "name"    => "customer-shipping-addresses",
                "action"  => "shippingAddresses",
                "access_keys" => ["customers"],
                "methods" => ["GET"],
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
                "methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/vendors/form-context",
                "name" => "vendors-form-context",
                "action" => "formContext",
                "access_keys" => ["vendors"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/vendors/check-duplicate",
                "name"    => "vendors-check-duplicate",
                "action"  => "checkDuplicate",
                "access_keys" => ["vendors"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/vendors/:id",
                "name" => "single-vendor",
                "action" => "index",
                "access_keys" => ["vendors"],
                "methods" => ["POST"],
            ],
        ],
        "purchaseorders" => [
            [
                "pattern" => "/purchase/orders",
                "name" => "purchase-orders",
                "action" => "index",
                "access_keys" => ["purchase_orders"],
                "methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/purchase/orders/form-context",
                "name" => "po-form-context",
                "action" => "formContext",
                "access_keys" => ["purchase_orders"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/purchase/orders/:id",
                "name" => "single-purchase-order",
                "action" => "entity",
                "access_keys" => ["purchase_orders"],
                "methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/purchase/orders/:id/status",
                "name" => "purchase-order-status",
                "action" => "status",
                "access_keys" => ["purchase_orders"],
                "methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/purchase/orders/:id/receive/form-context",
                "name" => "po-receive-form-context",
                "action" => "receiveFormContext",
                "access_keys" => ["purchase_orders"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/purchase/orders/:id/history",
                "name" => "purchase-order-history",
                "action" => "history",
                "access_keys" => ["purchase_orders"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/purchase/orders/:id/send-email",
                "name" => "po-send-email",
                "action" => "sendEmail",
                "access_keys" => ["purchase_orders"],
                "methods" => ["POST"],
            ],
        ],
        "purchasereceipts" => [
            [
                "pattern" => "/purchase/receipts",
                "name" => "purchase-receipts",
                "action" => "index",
                "access_keys" => ["purchase_receipts"],
                "methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/purchase/receipts/:id",
                "name" => "single-purchase-receipt",
                "action" => "entity",
                "access_keys" => ["purchase_receipts"],
                "methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/purchase/receipts/:id/form-context",
                "name" => "receipt-edit-form-context",
                "action" => "formContext",
                "access_keys" => ["purchase_receipts"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/purchase/receipts/:id/status",
                "name" => "purchase-receipt-status",
                "action" => "status",
                "access_keys" => ["purchase_receipts"],
                "methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/purchase/receipts/:id/history",
                "name" => "purchase-receipt-history",
                "action" => "history",
                "access_keys" => ["purchase_receipts"],
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
                "access_keys" => ["company_subscription"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/subscription/module",
                "name"    => "subscription-module",
                "action"  => "module",
                "access_keys" => ["company_subscription"],
                "methods" => ["POST"],
            ],
            [
                "pattern" => "/subscription/upgrade",
                "name"    => "subscription-upgrade",
                "action"  => "upgrade",
                "access_keys" => ["company_subscription"],
                "methods" => ["POST"],
            ],
            [
                "pattern" => "/subscription/downgrade",
                "name"    => "subscription-downgrade",
                "action"  => "downgrade",
                "access_keys" => ["company_subscription"],
                "methods" => ["POST"],
            ],
            [
                "pattern" => "/subscription/cancel",
                "name"    => "subscription-cancel",
                "action"  => "cancel",
                "access_keys" => ["company_subscription"],
                "methods" => ["POST"],
            ],
        ],
        /* End - Subscription */

        /* Start - Users */
        "users" => [
            [
                "pattern" => "/users",
                "action"  => "index",
                "access_keys" => ["company_users"],
                "methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/users/me",
                "action"  => "me",
                "methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/users/me/password",
                "action"  => "mePassword",
                "methods" => ["POST"],
            ],
            [
                "pattern" => "/users/form-context",
                "action"  => "formContext",
                "access_keys" => ["company_users"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/users/roles",
                "action"  => "roles",
                "access_keys" => ["company_roles_mgmt"],
                "methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/users/roles/form-context",
                "action"  => "rolesFormContext",
                "access_keys" => ["company_roles_mgmt"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/users/roles/:id/permissions",
                "name" => "role-permissions",
                "action" => "rolesPermissions",
                "access_keys" => ["company_roles_mgmt"],
                "methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/users/roles/:id",
                "name"    => "single-role",
                "action"  => "rolesEntity",
                "access_keys" => ["company_roles_mgmt"],
                "methods" => ["POST", "DELETE"],
            ],
            [
                "pattern" => "/users/roles/:id/status",
                "name"    => "role-status",
                "action"  => "rolesStatus",
                "access_keys" => ["company_roles_mgmt"],
                "methods" => ["POST"],
            ],
            [
                "pattern" => "/users/:id/status",
                "name"    => "user-status",
                "action"  => "status",
                "access_keys" => ["company_users"],
                "methods" => ["POST"],
            ],
            [
                "pattern" => "/users/:id",
                "name"    => "single-user",
                "action"  => "entity",
                "access_keys" => ["company_users"],
                "methods" => ["POST"],
            ],
        ],
        /* End - Users */

        /* Start - Teams */
        "teams" => [
            [
                "pattern" => "/company/teams",
                "action"  => "index",
                "access_keys" => ["company_teams"],
                "methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/company/teams/form-context",
                "action"  => "formContext",
                "access_keys" => ["company_teams"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/company/teams/:id",
                "name"    => "single-team",
                "action"  => "entity",
                "access_keys" => ["company_teams"],
                "methods" => ["POST", "DELETE"],
            ],
            [
                "pattern" => "/company/teams/:id/members",
                "name"    => "team-members",
                "action"  => "members",
                "access_keys" => ["company_teams"],
                "methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/company/teams/:id/members/:userId",
                "name"    => "team-member-remove",
                "action"  => "removeMember",
                "access_keys" => ["company_teams"],
                "methods" => ["DELETE"],
            ],
        ],
        /* End - Teams */

        /* Start - CRM module */
        "crmleads" => [
            [
                "pattern" => "/crm/leads",
                "name"    => "crm-leads",
                "action"  => "index",
                "access_keys" => ["crm_leads"],
                "methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/crm/leads/form-context",
                "name"    => "crm-leads-form-context",
                "action"  => "formContext",
                "access_keys" => ["crm_leads"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/crm/leads/pipeline",
                "name"    => "crm-leads-pipeline",
                "action"  => "pipeline",
                "access_keys" => ["crm_leads"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/crm/leads/reorder",
                "name"    => "crm-leads-reorder",
                "action"  => "reorder",
                "access_keys" => ["crm_leads"],
                "methods" => ["POST"],
            ],
            [
                "pattern" => "/crm/leads/export",
                "name"    => "crm-leads-export",
                "action"  => "export",
                "access_keys" => ["crm_leads"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/crm/leads/:id",
                "name"    => "single-crm-lead",
                "action"  => "entity",
                "access_keys" => ["crm_leads"],
                "methods" => ["GET", "POST"],
            ],
            [
                "pattern" => "/crm/leads/:id/status",
                "name"    => "crm-lead-status",
                "action"  => "status",
                "access_keys" => ["crm_leads"],
                "methods" => ["POST"],
            ],
            [
                "pattern" => "/crm/leads/:id/stage",
                "name"    => "crm-lead-stage",
                "action"  => "stage",
                "access_keys" => ["crm_leads"],
                "methods" => ["POST"],
            ],
            [
                "pattern" => "/crm/leads/:id/note",
                "name"    => "crm-lead-note",
                "action"  => "note",
                "access_keys" => ["crm_leads"],
                "methods" => ["POST"],
            ],
            [
                "pattern" => "/crm/leads/:id/history",
                "name"    => "crm-lead-history",
                "action"  => "history",
                "access_keys" => ["crm_leads"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/crm/leads/:id/convert-context",
                "name"    => "crm-lead-convert-context",
                "action"  => "convertContext",
                "access_keys" => ["crm_leads"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/crm/leads/:id/convert",
                "name"    => "crm-lead-convert",
                "action"  => "convert",
                "access_keys" => ["crm_leads"],
                "methods" => ["POST"],
            ],
        ],
        "crmintegrations" => [
            [
                "pattern" => "/crm/integrations",
                "name"    => "crm-integrations",
                "action"  => "index",
                "access_keys" => ["crm_integrations"],
                "methods" => ["GET", "POST", "DELETE"],
            ],
            [
                "pattern" => "/crm/integrations/form-context",
                "name"    => "crm-integrations-form-context",
                "action"  => "formContext",
                "access_keys" => ["crm_integrations"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/crm/integrations/:id",
                "name"    => "single-crm-integration",
                "action"  => "entity",
                "access_keys" => ["crm_integrations"],
                "methods" => ["POST"],
            ],
        ],
        "crmstages" => [
            [
                "pattern" => "/crm/stages",
                "name"    => "crm-stages",
                "action"  => "index",
                "access_keys" => ["crm_stages"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/crm/stages/form-context",
                "name"    => "crm-stages-form-context",
                "action"  => "formContext",
                "access_keys" => ["crm_stages"],
                "methods" => ["GET"],
            ],
            [
                "pattern" => "/crm/stages/:id",
                "name"    => "single-crm-stage",
                "action"  => "index",
                "access_keys" => ["crm_stages"],
                "methods" => ["POST", "DELETE"],
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
                "methods"     => ["GET", "POST"],
            ],
            [
                "pattern"     => "/manufacturing/boms/form-context",
                "name"        => "manufacturing-boms-form-context",
                "action"      => "formContext",
                "access_keys" => ["manufacturing_boms"],
                "methods"     => ["GET"],
            ],
            [
                "pattern"     => "/manufacturing/boms/:id",
                "name"        => "single-manufacturing-bom",
                "action"      => "entity",
                "access_keys" => ["manufacturing_boms"],
                "methods"     => ["GET", "POST", "DELETE"],
            ],
        ],
        "manufacturingorders" => [
            [
                "pattern"     => "/manufacturing/orders",
                "name"        => "manufacturing-orders",
                "action"      => "index",
                "access_keys" => ["manufacturing_orders"],
                "methods"     => ["GET", "POST"],
            ],
            [
                "pattern"     => "/manufacturing/orders/form-context",
                "name"        => "manufacturing-orders-form-context",
                "action"      => "formContext",
                "access_keys" => ["manufacturing_orders"],
                "methods"     => ["GET"],
            ],
            [
                "pattern"     => "/manufacturing/orders/:id",
                "name"        => "single-manufacturing-order",
                "action"      => "entity",
                "access_keys" => ["manufacturing_orders"],
                "methods"     => ["GET", "POST"],
            ],
            [
                "pattern"     => "/manufacturing/orders/:id/confirm",
                "name"        => "manufacturing-order-confirm",
                "action"      => "confirm",
                "access_keys" => ["manufacturing_orders"],
                "methods"     => ["POST"],
            ],
            [
                "pattern"     => "/manufacturing/orders/:id/cancel",
                "name"        => "manufacturing-order-cancel",
                "action"      => "cancel",
                "access_keys" => ["manufacturing_orders"],
                "methods"     => ["POST"],
            ],
            [
                "pattern"     => "/manufacturing/orders/:id/allocations",
                "name"        => "manufacturing-order-allocations",
                "action"      => "saveAllocation",
                "access_keys" => ["manufacturing_orders"],
                "methods"     => ["POST"],
            ],
            [
                "pattern"     => "/manufacturing/orders/:id/allocations/:allocationId/cancel",
                "name"        => "manufacturing-order-allocation-cancel",
                "action"      => "cancelAllocation",
                "access_keys" => ["manufacturing_orders"],
                "methods"     => ["POST"],
            ],
            [
                "pattern"     => "/manufacturing/orders/:id/output",
                "name"        => "manufacturing-order-output",
                "action"      => "recordOutput",
                "access_keys" => ["manufacturing_orders"],
                "methods"     => ["POST"],
            ],
            [
                "pattern"     => "/manufacturing/orders/:id/force-complete",
                "name"        => "manufacturing-order-force-complete",
                "action"      => "forceComplete",
                "access_keys" => ["manufacturing_orders"],
                "methods"     => ["POST"],
            ],
            [
                "pattern"     => "/manufacturing/orders/:id/returns",
                "name"        => "manufacturing-order-returns",
                "action"      => "recordMaterialReturn",
                "access_keys" => ["manufacturing_orders"],
                "methods"     => ["POST"],
            ],
        ],
        /* End - Manufacturing module */

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



        /* Start - Activities */
        "activities" => [
            [
                "pattern"     => "/activities",
                "name"        => "activities",
                "action"      => "index",
                "methods"     => ["GET", "POST"],
            ],
            [
                "pattern"     => "/activities/form-context",
                "name"        => "activities-form-context",
                "action"      => "formContext",
                "methods"     => ["GET"],
            ],
            [
                "pattern"     => "/activities/page-form-context",
                "name"        => "activities-page-form-context",
                "action"      => "pageFormContext",
                "access_keys" => ["activities"],
                "methods"     => ["GET"],
            ],
            [
                "pattern"     => "/activities/:id/status",
                "name"        => "activity-status",
                "action"      => "status",
                "methods"     => ["POST"],
            ],
            [
                "pattern"     => "/activities/:entity_type/:entity_id",
                "name"        => "entity-activities",
                "action"      => "entityActivities",
                "methods"     => ["GET"],
            ],
            [
                "pattern"     => "/activities/:id",
                "name"        => "single-activity",
                "action"      => "entity",
                "methods"     => ["POST", "DELETE"],
            ],
        ],
        /* End - Activities */

        /* Start - Dashboard */
        "dashboard" => [
            [
                "pattern"     => "/dashboard/summary",
                "name"        => "dashboard-summary",
                "action"      => "summary",
                "access_keys" => ["dashboard"],
                "methods"     => ["GET"],
            ],
            [
                "pattern"     => "/dashboard/operator-summary",
                "name"        => "dashboard-operator-summary",
                "action"      => "operatorSummary",
                "access_keys" => ["dashboard"],
                "methods"     => ["GET"],
            ],
            [
                "pattern"     => "/dashboard/sales-by-month",
                "name"        => "dashboard-sales-by-month",
                "action"      => "salesByMonth",
                "access_keys" => ["dashboard"],
                "methods"     => ["GET"],
            ],
            [
                "pattern"     => "/dashboard/top-customers",
                "name"        => "dashboard-top-customers",
                "action"      => "topCustomers",
                "access_keys" => ["dashboard"],
                "methods"     => ["GET"],
            ],
            [
                "pattern"     => "/dashboard/leads-by-month",
                "name"        => "dashboard-leads-by-month",
                "action"      => "leadsByMonth",
                "access_keys" => ["dashboard"],
                "methods"     => ["GET"],
            ],
        ],
        /* End - Dashboard */
    ]
];
