# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

---

## Project Overview

**Opsify** is a multi-tenant SaaS for inventory, purchasing, sales, and CRM management. Built on **TinyPHP** — a custom lightweight PHP MVC framework, located in `/TinyPHP/`.

**Current branch:** `user-role-access` — role-based access control system.

**Implemented modules:** Products, Inventory, Sales (orders + deliveries), Purchasing (orders + receipts), Customers, Vendors, CRM (leads, stages, pipeline, integrations), Users & Roles, Subscriptions, Attachments, Activities, Webhooks.

---

## Running the Application

PHP/Apache application. **No build step, no npm, no Node.**

```bash
composer install
cp .env.example .env   # configure DB, JWT, mail
```

- **Web server:** Apache with mod_rewrite — `.htaccess` in `/Public/` routes everything to `index.php`
- **Entry point:** `Public/index.php` → `App/bootstrap/app.php` → `TinyPHP_Front::run()`
- **No automated tests** — test manually by running the app via Apache/PHP

---

## Module System

Three modules defined in `App/config/modules.php`. The `app` module is always available (default), only additional ones are registered:

| Module | Purpose | Controller path |
|--------|---------|----------------|
| `app` | Server-side rendered Blade pages | `App/http/app/controllers/` |
| `api` | JSON REST API (prefix: `/api/`) | `App/http/api/controllers/` |
| `admin` | Admin panel (not yet used) | `App/http/admin/controllers/` |

---

## Routes

Routes are arrays defined in `App/routes/app.php` and `App/routes/api.php`. Each route entry has `pattern`, `action`, optional `name`, `methods`, and `access_key`.

`access_key` is a feature key checked by middleware against the user's accessible features. If a route has no `access_key`, access is not feature-gated (only authentication checked).

### Web Routes (`App/routes/app.php`)

| Controller group | Pattern | Action | Access key |
|---|---|---|---|
| `front` | `/` | `home` | — |
| `front` | `/about-us` | `aboutus` | — |
| `front` | `/contact-us` | `contactus` | — |
| `auth` | `/login` | `login` | — |
| `auth` | `/forgot-password` | `forgotpassword` | — |
| `auth` | `/reset-password` | `resetpassword` | — |
| `dashboard` | `/dashboard` | `index` | `dashboard` |
| `companies` | `/register` | `register` | — |
| `companies` | `/companies/activate` | `activate` | — |
| `companies` | `/settings/general` | `profile` | `companies.general` |
| `products` | `/products` | `index` | `products` |
| `prodcategories` | `/products/categories` | `index` | `products.categories` |
| `invsettings` | `/settings/inventory` | `index` | `settings.inventory` |
| `locations` | `/company/locations` | `index` | `company.locations` |
| `inventory` | `/inv/adjustments` | `adjustments` | `inv.adjustments` |
| `invproducts` | `/inv/products/:id/stock-locations` | `stockLocations` | `inv.stocklocation` |
| `salesorders` | `/sales/quotations` | `quotations` | `sales.quotations` |
| `salesorders` | `/sales/orders` | `index` | `sales.orders` |
| `salesorders` | `/sales/orders/:id` | `edit` | `sales.order.edit` |
| `salesorders` | `/sales/orders/:id/pdf` | `pdf` | `sales.order.pdf` |
| `salesorders` | `/sales/orders/:id/print-view` | `printView` | `sales.order.printview` |
| `salesdeliveries` | `/sales/deliveries` | `index` | `sales.deliveries` |
| `salesdeliveries` | `/sales/deliveries/:id` | `edit` | `sales.delivery.edit` |
| `customers` | `/customers` | `index` | `customers` |
| `vendors` | `/vendors` | `index` | `vendors` |
| `purchaseorders` | `/purchase/orders` | `index` | `purchase.orders` |
| `purchaseorders` | `/purchase/orders/:id` | `edit` | `purchase.order.view.edit` |
| `purchasereceipts` | `/purchase/receipts` | `index` | `purchase.receipts` |
| `purchasereceipts` | `/purchase/receipts/:id` | `edit` | `purchase.receipt.view.edit` |
| `attachments` | `/attachments/:id` | `download` | `attachments` |
| `subscriptions` | `/settings/subscription` | `index` | `subscription` |
| `billing` | `/settings/billing` | `index` | `billing` |
| `subscriptionexpired` | `/subscription/expired` | `index` | `subscription.expired` |
| `users` | `/company/users` | `index` | `company.users` |
| `users` | `/company/users/roles` | `roles` | `company.users.roles` |
| `crmleads` | `/crm/leads` | `index` | `crm.leads` |
| `crmleads` | `/crm/pipeline` | `pipeline` | `crm.pipeline` |
| `crmleads` | `/crm/leads/:id` | `edit` | `crm.leads.edit` |
| `crmstages` | `/crm/stages` | `index` | `crm.stages` |
| `crmintegrations` | `/crm/integrations` | `index` | `crm.integrations` |

### API Routes (`App/routes/api.php`) — all prefixed `/api/`

| Controller group | Pattern | Methods | Action | Access key |
|---|---|---|---|---|
| `auth` | `/auth/login` | POST | `login` | — |
| `auth` | `/auth/refresh-token` | POST | `refreshToken` | `refresh.token` |
| `auth` | `/auth/logout` | POST | `logout` | `logout` |
| `companies` | `/companies/register` | POST | `register` | — |
| `companies` | `/companies/activate` | POST | `activate` | — |
| `companies` | `/company/profile` | GET, POST | `profile` | `company.profile.view.edit` |
| `prodcategories` | `/products/categories` | GET | `index` | `products.categories` |
| `prodcategories` | `/products/categories/form-context` | GET | `formContext` | `products.category.formcontext` |
| `prodcategories` | `/products/categories/:id` | POST, DELETE | `index` | `products.category.edit` |
| `productmasters` | `/product-masters` | GET, DELETE | `index` | — |
| `products` | `/products` | GET, POST | `index` | `products` |
| `products` | `/products/form-context` | GET | `formContext` | `product.formcontext` |
| `products` | `/products/:id` | POST, DELETE | `index` | `product.edit` |
| `locations` | `/company/locations` | GET | `index` | `company.locations` |
| `locations` | `/company/locations/form-context` | GET | `formContext` | `company.location.formcontext` |
| `locations` | `/company/locations/:id` | POST, DELETE | `index` | `company.location.view.edit` |
| `inventory` | `/inv/adjustments` | GET | `adjustments` | `inv.adjustments` |
| `invproducts` | `/inv/products/:id/stock-locations` | GET | `stockLocations` | `inv.stocklocation` |
| `invproducts` | `/inv/products/:id/stock/adjust` | POST | `adjustStock` | `inv.stock.adjust.save` |
| `invproducts` | `/inv/products/:id/stock/adjust/form-context` | GET | `adjustFormContext` | `inv.stock.adjust.formcontext` |
| `invproducts` | `/inv/products/:id/serial-or-lot-numbers` | GET | `serialOrLotNumbers` | `inv.stock.serial.lot.numbers` |
| `invsequence` | `/inv/sequence/generate` | POST | `generate` | — |
| `quotations` | `/sales/quotations` | GET | `index` | `sales.quotations` |
| `salesorders` | `/sales/orders` | GET, POST | `index` | `sales.orders` |
| `salesorders` | `/sales/orders/form-context` | GET | `formContext` | `sales.order.formcontext` |
| `salesorders` | `/sales/orders/customers/search` | GET | `customersSearch` | `sales.orders.customer.search` |
| `salesorders` | `/sales/orders/:id` | GET, POST | `entity` | `sales.orders.entity` |
| `salesorders` | `/sales/orders/:id/status` | POST | `status` | `sales.order.status` |
| `salesorders` | `/sales/orders/:id/history` | GET | `history` | `sales.order.history` |
| `salesorders` | `/sales/orders/:id/send-email` | POST | `sendEmail` | `sales.order.send.email` |
| `salesdeliveries` | `/sales/deliveries` | GET, POST | `index` | `sales.deliveries` |
| `salesdeliveries` | `/sales/deliveries/form-context` | GET | `formContext` | `sales.delivery.formcontext` |
| `salesdeliveries` | `/sales/deliveries/so-search` | GET | `soSearch` | `sales.delivery.sosearch` |
| `salesdeliveries` | `/sales/deliveries/:id` | GET, POST | `entity` | `sales.delivery.entity` |
| `salesdeliveries` | `/sales/deliveries/:id/status` | POST | `status` | `sales.delivery.status` |
| `salesdeliveries` | `/sales/deliveries/:id/history` | GET | `history` | `sales.delivery.history` |
| `customers` | `/customers` | GET | `index` | `customers` |
| `customers` | `/customers/form-context` | GET | `formContext` | `customer.formcontext` |
| `customers` | `/customers/check-duplicate` | GET | `checkDuplicate` | `customer.check.duplicate` |
| `customers` | `/customers/search` | GET | `search` | `customer.search` |
| `customers` | `/customers/:id` | POST | `index` | `customer.edit` |
| `customers` | `/customers/:id/addresses` | POST | `storeAddress` | `customer.address` |
| `vendors` | `/vendors` | GET | `index` | `vendors` |
| `vendors` | `/vendors/form-context` | GET | `formContext` | `vendor.formcontext` |
| `vendors` | `/vendors/check-duplicate` | GET | `checkDuplicate` | `vendors.check.duplicate` |
| `vendors` | `/vendors/:id` | POST | `index` | `vendor.edit` |
| `purchaseorders` | `/purchase/orders` | GET, POST | `index` | `purchase.orders` |
| `purchaseorders` | `/purchase/orders/form-context` | GET | `formContext` | `purchase.order.formcontext` |
| `purchaseorders` | `/purchase/orders/:id` | GET, POST | `entity` | `purchase.order.view.edit` |
| `purchaseorders` | `/purchase/orders/:id/status` | GET, POST | `status` | `purchase.order.status` |
| `purchaseorders` | `/purchase/orders/:id/receive/form-context` | GET | `receiveFormContext` | `purchase.order.receive.formcontext` |
| `purchaseorders` | `/purchase/orders/:id/history` | GET | `history` | `purchase.order.history` |
| `purchasereceipts` | `/purchase/receipts` | GET, POST | `index` | `purchase.receipts` |
| `purchasereceipts` | `/purchase/receipts/:id` | GET, POST | `entity` | `purchase.receipt.view.edit` |
| `purchasereceipts` | `/purchase/receipts/:id/form-context` | GET | `formContext` | `purchase.receipt.formcontext` |
| `purchasereceipts` | `/purchase/receipts/:id/status` | GET, POST | `status` | `purchase.receipt.status` |
| `purchasereceipts` | `/purchase/receipts/:id/history` | GET | `history` | `purchase.receipt.history` |
| `subscriptions` | `/subscription/summary` | GET | `summary` | `subscription.summary` |
| `subscriptions` | `/subscription/module` | POST | `module` | `subscription.module` |
| `subscriptions` | `/subscription/upgrade` | POST | `upgrade` | `subscription.upgrade` |
| `subscriptions` | `/subscription/downgrade` | POST | `downgrade` | `subscription.downgrade` |
| `subscriptions` | `/subscription/cancel` | POST | `cancel` | `subscription.cancel` |
| `users` | `/users` | GET, POST | `index` | `company.users` |
| `users` | `/users/me` | GET, POST | `me` | `company.user.me` |
| `users` | `/users/me/password` | POST | `mePassword` | `company.user.password` |
| `users` | `/users/form-context` | GET | `formContext` | `company.user.formcontext` |
| `users` | `/users/roles` | GET, POST | `roles` | `company.users.roles` |
| `users` | `/users/roles/form-context` | GET | `rolesFormContext` | `company.user.role.formcontext` |
| `users` | `/users/roles/:id/permissions` | GET, POST | `rolesPermissions` | `company.user.roles.permissions` |
| `users` | `/users/roles/:id` | POST | `rolesEntity` | `company.users.roles.view.edit` |
| `users` | `/users/:id/status` | POST | `status` | `user.status` |
| `users` | `/users/:id` | POST | `entity` | `user.view.edit` |
| `crmleads` | `/crm/leads` | GET, POST | `index` | `crm.leads` |
| `crmleads` | `/crm/leads/form-context` | GET | `formContext` | `crm.leads.formcontext` |
| `crmleads` | `/crm/leads/pipeline` | GET | `pipeline` | `crm.leads.pipeline` |
| `crmleads` | `/crm/leads/reorder` | POST | `reorder` | `crm.leads.reorder` |
| `crmleads` | `/crm/leads/:id` | GET, POST | `entity` | `crm.leads.entity` |
| `crmleads` | `/crm/leads/:id/status` | POST | `status` | `crm.leads.status` |
| `crmleads` | `/crm/leads/:id/stage` | POST | `stage` | `crm.leads.stage` |
| `crmleads` | `/crm/leads/:id/note` | POST | `note` | `crm.leads.note` |
| `crmleads` | `/crm/leads/:id/history` | GET | `history` | `crm.leads.history` |
| `crmleads` | `/crm/leads/:id/convert-context` | GET | `convertContext` | `crm.leads.convert.formcontext` |
| `crmleads` | `/crm/leads/:id/convert` | POST | `convert` | `crm.leads.convert` |
| `crmstages` | `/crm/stages` | GET | `index` | `crm.stages` |
| `crmstages` | `/crm/stages/form-context` | GET | `formContext` | `crm.stage.formcontext` |
| `crmstages` | `/crm/stages/:id` | POST, DELETE | `index` | `crm.stage.view.edit` |
| `crmintegrations` | `/crm/integrations` | GET, POST, DELETE | `index` | `crm.integrations` |
| `crmintegrations` | `/crm/integrations/form-context` | GET | `formContext` | `crm.integration.formcontext` |
| `crmintegrations` | `/crm/integrations/:id` | POST | `entity` | `crm.integration.view.edit` |
| `webhooks` | `/webhooks/:source/:token` | POST | `receive` | — |
| `dashboard` | `/dashboard/summary` | GET | `summary` | `dashboard.summary` |
| `activities` | `/activities` | GET, POST | `index` | `activities` |
| `activities` | `/activities/form-context` | GET | `formContext` | `activity.formcontext` |
| `activities` | `/activities/:id/done` | POST | `done` | `activity.done` |
| `activities` | `/activities/:id` | POST, DELETE | `entity` | `activity.view.edit` |

---

## Authentication

| Context | Mechanism |
|---------|-----------|
| Web (`app`) | Session-based. `Middleware_AppAuth` validates `access_token` cookie, renews using `refresh_token` cookie. Unauthenticated → redirect to `/login`. Subscription expired → redirect to `/subscription/expired`. |
| API (`api`) | JWT Bearer. `Middleware_ApiAuth` validates `Authorization: Bearer <token>`. Unauthenticated → 401. |

- Auth services: `App/service/Auth.php`, `App/service/AuthToken.php`
- JWT config: `App/config/jwt.php` — secret from env, access TTL 60 min, refresh TTL 14 days, HS256
- `Service_Auth::user()` validates token and checks user + company active status
- Tokens stored in `auth_tokens` table

**Auth-exempt routes:**
- Web: `front::*`, `auth::*`, `companies::{register,activate}`, `subscriptionexpired::*`, `salesorders::printView`
- API: `auth::login`, `companies::{register,activate}`, `webhooks::receive`

---

## Database

- **ORM:** Laravel Illuminate Database (Eloquent query builder) wrapped by `TinyPHP_ActiveRecord`
- **Default connection:** `main_db` — all operational/company data
- **Platform DB:** `platform_db` — companies, users, auth tokens, subscriptions, modules, roles, features. Currently points to the same DB as `main_db` (same `.env` vars). Separated in config for future per-company DB isolation.
- **Reporting DB:** `mysql_reporting` — secondary analytics connection
- **Schema tracking:** Manual, in `/Updates/db_changes.sql` — **no migration runner**, apply manually
- **Models:** `App/models/`

Use `DB()` helper to get the default connection, `DB('platform_db')` for platform queries.

---

## Models

All models extend `TinyPHP_ActiveRecord`. Naming convention: `Models_ClassName`. Key properties:

```php
class Models_CrmLead extends TinyPHP_ActiveRecord
{
    public $tableName = "crm_leads";             // table name (instance property, not static)
    // protected $dbConnectionName = "platform_db"; // override connection if needed (default: main_db)

    // Declare all columns as public properties with defaults
    public $company_id = 0;
    public $status = "active";
    // ... all other columns ...

    protected $dbIgnoreFields = ["id"];           // fields excluded from INSERT/UPDATE

    public function init() {
        // Register lifecycle hooks
        $this->addListener('beforeCreate', [$this, 'doBeforeCreate']);
        $this->addListener('beforeUpdate', [$this, 'doBeforeUpdate']);

        // Register lazy-load relationships
        $this->addLazyLoadProperty('line_items');
    }

    protected function doBeforeCreate() {
        $date = date("Y-m-d H:i:s");
        $this->created_at = $date;
        $this->updated_at = $date;
        return !$this->hasErrors(); // must return bool
    }

    protected function doBeforeUpdate() {
        $this->updated_at = date("Y-m-d H:i:s");
        return !$this->hasErrors();
    }

    // Implement lazy loading for registered properties
    protected function lazyLoadProperty($property) {
        if ($property === 'line_items') {
            return $this->getLineItems();
        }
    }
}
```

**ActiveRecord CRUD:**
```php
$lead = new Models_CrmLead($id);   // fetch by id; $lead->isEmpty if not found
$lead->company_id = $companyId;
$leadId = $lead->create();          // INSERT; returns new id or false
$lead->update();                    // UPDATE; returns bool
$lead->delete();                    // DELETE; returns bool
$lead->toArray();                   // all public properties as array
$lead->fillFromArray($data, ['id', 'created_at']); // bulk-fill, second arg = skip keys
$lead->query($sql, $params);        // raw query returning stdClass[]
```

**Key models and their tables:**

| Model | Table | Notes |
|---|---|---|
| `Models_Company` | `companies` | platform_db |
| `Models_User` | `users` | company_id scoped |
| `Models_CompanyRole` | `company_roles` | |
| `Models_UserRole` | `user_roles` | junction: user_id, role_id, company_id |
| `Models_RoleAccessGrant` | `role_access_grants` | role_id, access_type='feature'\|'module', access_id |
| `Models_Feature` | `features` | key, name, module_id, access_level='public'\|'admin'\|'super_admin' |
| `Models_Module` | `modules` | key, name, is_active |
| `Models_AuthToken` | `auth_tokens` | |
| `Models_CompanySubscription` | `company_subscriptions` | status='trial'\|'pilot'\|'active'\|'past_due'\|'suspended'\|'cancelled' |
| `Models_CompanySubscriptionModule` | `company_subscription_modules` | |
| `Models_Product` | `products` | stock_tracking_method='fifo'\|'lifo'\|'standard'\|'serial'\|'lot' |
| `Models_ProdCategory` | `product_categories` | |
| `Models_Customer` | `customers` | customer_type='company'\|'individual' |
| `Models_CustomerAddress` | `customer_addresses` | address_type='billing'\|'shipping'\|'other' |
| `Models_CustomerContact` | `customer_contacts` | |
| `Models_Vendor` | `vendors` | |
| `Models_Location` | `company_locations` | location_type: head_office, branch, warehouse, store, factory, workshop, customer_site, vendor_site, virtual |
| `Models_SalesOrder` | `sales_orders` | status='draft'\|'confirmed'\|'cancelled'\|'delivered'; lazy: line_items, customer, location |
| `Models_SalesOrderItem` | `sales_order_items` | |
| `Models_SalesOrderHistory` | `sales_order_history` | |
| `Models_SalesDelivery` | `sales_deliveries` | status='draft'\|'confirmed'\|'completed' |
| `Models_SalesDeliveryItem` | `sales_delivery_items` | |
| `Models_PurchaseOrder` | `purchase_orders` | status='draft'\|'confirmed'\|'cancelled'\|'received' |
| `Models_PurchaseOrderItem` | `purchase_order_items` | |
| `Models_PurchaseOrderGrn` | `purchase_order_grns` | GRN = Goods Receipt Note |
| `Models_InvProductStock` | `inv_product_stock` | company_id, product_id, location_id, qty |
| `Models_InvStockMovement` | `inv_stock_movements` | movement_type enum (see constants.php) |
| `Models_InvSerial` | `inv_serials` | |
| `Models_CrmLead` | `crm_leads` | status='active'\|'won'\|'lost' |
| `Models_CrmLeadHistory` | `crm_lead_history` | log_type enum |
| `Models_CrmStage` | `crm_stages` | is_won, is_lost flags |
| `Models_Activity` | `activities` | |
| `Models_Attachment` | `attachments` | related_type + related_id polymorphic |
| `Models_Sequence` | `sequences` | entity_type, next_number, prefix, suffix, pad_length |
| `Models_WebhookIntegration` | `webhook_integrations` | source='indiamart' |
| `Models_WebhookLog` | `webhook_logs` | |
| `Models_PaymentTerm` | `payment_terms` | |
| `Models_Tax` | `taxes` | |

---

## Service Layer

All services extend `Service_Base`. Constructor requires `Service_TenantContext`. Never instantiate a service without a context.

```php
class Service_Base {
    protected $db;                      // TinyPHP_DB instance for this company
    protected Service_TenantContext $context;

    public function __construct(Service_TenantContext $context) {
        $this->context = $context;
        $this->db = Service_TenantDBResolver::resolve($context->companyId);
    }

    public function addError($err, $idx = null): void
    public function getErrors(): array
    public function hasErrors(): bool
    public function resetErrors(): void
}
```

**Service instantiation pattern (inside controllers):**
```php
private function serviceCrmLead(): Service_Crm_Lead {
    return new Service_Crm_Lead(tenantContext());
}
```

**Service return convention:**
- Success: `["success" => true, "data" => [...]]`
- Validation failure: `["success" => false, "errors" => [...field => message...]]`
- Hard errors: throw `Service_Exception($message, $httpCode)`

### Key Services

| Service | Location | Key methods |
|---|---|---|
| `Service_Auth` | `App/service/Auth.php` | `user()`, `login()`, `renewAccessToken()`, `logout()` |
| `Service_AuthToken` | `App/service/AuthToken.php` | `generateTokens()`, `validateAccessToken()`, `refreshAccessToken()` (static) |
| `Service_TenantContext` | `App/service/TenantContext.php` | `hydrate()`, `canAccess($featureKey)`, `hasModule($moduleKey)` |
| `Service_AccessControl` | `App/service/AccessControl.php` | `canAccess()`, `companyCanAccess()`, `userCanAccess()`, `userIsSuperAdmin()`, `getUserAccessibleFeatureKeys()` |
| `Service_Subscription` | `App/service/Subscription.php` | `getCurrent()`, `isAccessible()`, `getActiveModuleKeys()`, `getAccessibleFeatureKeys()` |
| `Service_Company` | `App/service/Company.php` | Company CRUD, profile, settings |
| `Service_User` | `App/service/User.php` | User CRUD, password, status |
| `Service_Product` | `App/service/Product.php` | Product CRUD, pricing, taxes |
| `Service_Customer` | `App/service/Customer.php` | Customer CRUD, `checkDuplicate()`, addresses, contacts |
| `Service_Vendor` | `App/service/Vendor.php` | Vendor CRUD |
| `Service_Activity` | `App/service/Activity.php` | Activity CRUD |
| `Service_Attachment` | `App/service/Attachment.php` | `saveFromBase64()`, `groupFor($relatedType, $ids)` |
| `Service_Sequence` | `App/service/Sequence.php` | `nextCommit($entityType)` — row-level locking |
| `Service_So_Order` | `App/service/So/Order.php` | `getFormContext()`, `list()`, `save()`, `show()`, `updateStatus()`, `sendEmail()`, `getHistory()` |
| `Service_So_Delivery` | `App/service/So/Delivery.php` | Sales delivery CRUD |
| `Service_Po_Order` | `App/service/Po/Order.php` | Purchase order CRUD |
| `Service_Po_Grn` | `App/service/Po/Grn.php` | GRN processing |
| `Service_Inv_Stock` | `App/service/Inv/Stock.php` | Stock tracking, adjustments |
| `Service_Inv_Movement` | `App/service/Inv/Movement.php` | Stock movement logging |
| `Service_Crm_Lead` | `App/service/Crm/Lead.php` | `create()`, `update()`, `show()`, `updateStatus()`, `updateStage()`, `reorder()`, `addNote()`, `getPipelineData()`, `getFormContext()`, `getConvertContext()`, `convert()`, `getHistory()`, `logHistory()` |
| `Service_Crm_Stage` | `App/service/Crm/Stage.php` | Stage CRUD |
| `Service_Webhook_Integration` | `App/service/Webhook/Integration.php` | Integration CRUD |
| `Service_Webhook_Processor` | `App/service/Webhook/Processor.php` | Event processing |
| `Service_Webhook_Parser_Indiamart` | `App/service/Webhook/Parser/Indiamart.php` | Indiamart parser |

---

## Access Control System

Feature-based RBAC fully implemented on the `user-role-access` branch.

### Data model

```
modules         → logical groupings (crm, sales, inventory, ...)
features        → individual permissions (crm.leads, sales.orders, ...)
                  access_level = 'public' | 'admin' | 'super_admin'
module_feature_map → which features belong to which module

company_subscriptions → active subscription per company
company_subscription_modules → which modules the company has subscribed to

company_roles   → roles per company; is_super=1 means super-admin (all access)
user_roles      → junction: user ↔ role ↔ company
role_access_grants → role_id, access_type='feature'|'module', access_id
```

### How access is checked

`Service_AccessControl::canAccess($companyId, $userId, $featureKey)`:
1. **Company check:** Is the feature in an active subscribed module? (`companyCanAccess`)
2. **User check:** Does the user's role grant this feature? (`userCanAccess`)
   - Super role (`is_super=1`) → passes everything
   - Direct feature grant (`access_type='feature'`)
   - Module grant (`access_type='module'`) covering the feature's module
   - `super_admin` features require super role explicitly

`Service_TenantContext` is hydrated once per request by middleware and stores:
- `$isSuperAdmin` — bool
- `$accessibleFeatureKeys` — array of feature key strings the user can reach
- `$activeModuleKeys` — array of module keys in the company's subscription

Use `tenantContext()->canAccess('feature.key')` in controllers/services instead of re-checking.

---

## Middleware

Registered in `App/config/middlewares.php`:

```
Global (all requests):
  Middleware_Log

App module:
  Middleware_Csrf         — validates CSRF token on POST/PUT/PATCH/DELETE
  Middleware_AppAuth      — validates access_token cookie, renews via refresh_token,
                            redirects to /login if unauthenticated,
                            redirects to /subscription/expired if subscription lapsed,
                            hydrates Service_TenantContext,
                            checks route access_key against user's accessible features
  Middleware_AppRedirectIfAuth — redirects authenticated users away from login/register

API module:
  Middleware_ApiAuth      — validates JWT Bearer token (401 if missing/invalid),
                            checks subscription (402 if expired/suspended),
                            hydrates Service_TenantContext,
                            checks route access_key (403 if not accessible)
```

**Middleware_ApiAuth exemptions** (no auth/subscription check): `auth::login`, `companies::register`, `companies::activate`, `webhooks::receive`

**Middleware_AppAuth exemptions**: `front::*`, `auth::*`, `companies::{register,activate}`, `subscriptionexpired::*`, `salesorders::printView`

---

## Controllers

### API Controllers (`App/http/api/controllers/`)

Extend `TinyPHP_Controller`. Always call `$this->setNoRenderer(true)` in `init()`. Return JSON via `response()->sendJson()`.

```php
class Api_CrmLeadsController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function serviceCrmLead(): Service_Crm_Lead {
        return new Service_Crm_Lead(tenantContext());
    }

    // GET /api/crm/leads/:id
    public function entityAction(TinyPHP_Request $request) {
        if ($request->isMethod("get")) {
            $id = $request->getInput("id", "Int", 0);
            $data = $this->serviceCrmLead()->show($id);
            return response($data)->sendJson();
        }
        if ($request->isMethod("post")) {
            $inputs = $request->getInputs();
            $result = $this->serviceCrmLead()->update($id, $inputs);
            if ($result["success"]) {
                return response($result["data"], "Updated successfully", 200)->sendJson();
            }
            return response([], "Validation failed", 422)->errors($result["errors"])->sendJson();
        }
    }
}
```

**`response()` builder:**
```php
response($data, $message, $statusCode)  // defaults: [], '', 200
    ->errors($errorsArray)
    ->sendJson()
```

**Reading request inputs:**
```php
$request->getInput("field", "Int"|"String"|"Float"|"Bool", $default)
$request->getInputs()     // all inputs as assoc array
$request->isMethod("get"|"post"|"delete")
$request->getParams()     // URL params (:id etc)
```

### Web Controllers (`App/http/app/controllers/`)

Extend `TinyPHP_Controller`. Return HTML via `$this->render()`.

```php
class App_CrmleadsController extends TinyPHP_Controller {

    public function indexAction(TinyPHP_Request $request) {
        $data = ['title' => 'CRM Leads'];
        return $this->render('app.crm.leads.index', $data);
    }
}
```

---

## Views

Blade templates in `App/resources/views/`. Use `$this->render('dot.notation.path', $data)` from controllers.

**Layouts:**
- `layouts/app.blade.php` — authenticated pages (sidebar, nav, footer)
- `layouts/front.blade.php` — public pages

**Drawer components** (`app/components/drawers/**/add-edit.blade.php`): modal slide-in panels for create/edit forms. Each drawer is included in the parent page and opened by JS. Follow this pattern for all new form UIs.

**View directory structure:**
```
app/
├── auth/                crm/          customers/     dashboard/
├── components/
│   └── drawers/
│       ├── activities/  crm/leads/    crm/stages/    crm/integrations/
│       ├── customers/   vendors/      users/         products/
│       ├── purchase-orders/           sales-orders/  sales-deliveries/
│       ├── inventory/products/        company/locations/
│       └── categories/
├── inventory/  locations/  products/  purchaseorders/  purchasereceipts/
├── salesorders/  salesdeliveries/  subscriptions/  users/  vendors/
errors/
layouts/
partial/app/   partial/common/   partial/front/
```

---

## Key Coding Conventions

### Routes

```php
// Route entry format
[
    "pattern" => "/crm/leads/:id",
    "name"    => "single-crm-lead",     // optional
    "action"  => "edit",                // maps to editAction() in controller
    "access_key" => "crm.leads.edit",   // feature key checked by middleware
    "methods" => ["GET", "POST"],       // omit for all methods
]
```

### Services

Services extend `Service_Base`, receive `TenantContext`, throw `Service_Exception` on hard errors, return `["success" => bool, ...]` for user-facing validation:

```php
class Service_Crm_Lead extends Service_Base {

    public function create(array $payload): array {
        $this->normalizePayload($payload);
        $this->validatePayload($payload);

        if ($this->hasErrors()) {
            return ["success" => false, "errors" => $this->getErrors()];
        }

        $this->db->startTransaction();
        try {
            // ... persist ...
            $this->db->commit();
            return ["success" => true, "data" => ["id" => $id]];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
```

### Sequence Numbers

Use `Service_Sequence::nextCommit($entityType)` for SO/PO/CRM lead codes. Uses row-level locking (`FOR UPDATE`) to prevent duplicates under concurrent load. `Service_Inv_Sequence` for serial/lot numbers.

### Validation Messages

Use `validationErrMsg($key, $field)` — pulls messages from `App/config/errors.php`. Never use inline strings for validation errors.

### Multi-tenancy

Every query must be scoped by `company_id`. Get it from `$this->context->companyId` inside services. Never query without tenant scope.

---

## Database Query Patterns

Use `$this->db` (in services) or `DB()` helper (anywhere):

```php
// In a service method:
$rows = $this->db->fetchAll("SELECT * FROM crm_leads WHERE company_id = ? AND status = ?", [$companyId, 'active']);
$row  = $this->db->fetchOne("SELECT * FROM crm_stages WHERE id = ? LIMIT 1", [$id]);
$this->db->insert("crm_stages", ['company_id' => 1, 'name' => 'New']);
$this->db->update("crm_stages", ['name' => 'Updated'], "id = $id");
$this->db->execute("DELETE FROM crm_stages WHERE id = ?", [$id]);

// Transactions:
$this->db->startTransaction();
try {
    // ... queries ...
    $this->db->commit();
} catch (Exception $e) {
    $this->db->rollBack();
    throw $e;
}

// Raw DB() helper (outside services):
$row = DB()->fetchOne("SELECT ...", $params);
$row = DB('platform_db')->fetchOne("SELECT ...", $params);  // platform tables
```

`fetchAll` and `fetchOne` return `stdClass` objects (or arrays of them). Cast with `(array) $row` or `get_object_vars($row)` when needed.

---

## Config Files (`App/config/`)

| File | Contents |
|---|---|
| `database.php` | 3 connections: `main_db` (default), `platform_db`, `mysql_reporting` |
| `modules.php` | Module registration: `api`, `admin` |
| `middlewares.php` | Middleware stacks per module |
| `constants.php` | `company.location_types`, `inventory.stock_movement_type` |
| `errors.php` | Validation error message keys |
| `jwt.php` | JWT secret, TTL 60 min, refresh TTL 14 days, HS256 |
| `hashing.php` | bcrypt (cost 10) or argon2id |
| `commands.php` | CLI commands: `test`, `webhook:process` |
| `cors.php` | CORS for `api/*` paths |
| `timezones.php` | Timezone list |

---

## TinyPHP Framework (`/TinyPHP/`)

Custom MVC framework ~32 classes. Do not modify unless extending framework capabilities.

**Key classes:**
- `TinyPHP_ActiveRecord` — base model; `create()`, `update()`, `delete()`, `query()`, `execute()`, `toArray()`, `fillFromArray()`, `addListener()`, `addLazyLoadProperty()`
- `TinyPHP_DB` — query builder; `fetchOne()`, `fetchAll()`, `insert()`, `update()`, `delete()`, `execute()`, `startTransaction()`, `commit()`, `rollBack()`
- `TinyPHP_Request` — singleton; `getInput()`, `getInputs()`, `isMethod()`, `getParams()`, `getHeader()`
- `TinyPHP_Response` — builder; `data()`, `message()`, `status()`, `errors()`, `meta()`, `sendJson()`
- `TinyPHP_Controller` — base; `render()`, `setNoRenderer()`, `setTitle()`, `setViewVar()`
- `TinyPHP_Router` — route matcher
- `TinyPHP_Front` — application dispatcher singleton
- `TinyPHP_Jwt` — `encode()`, `decode()`
- `TinyPHP_Hash` — `hash()`, `verify()`
- `TinyPHP_Session` — `init()`, `get()`, `set()`, `has()`, `destroy()`

**Global helpers (`TinyPHP/Support/helpers.php`):**
```php
DB(?string $connection = null): TinyPHP_DB
config(string $key, $default = null): mixed
response($data, $message, $statusCode): TinyPHP_Response
url(string $path): string
asset(string $path): string
auth(): Service_Auth
env(string $key, $default): mixed
redirect(string $path, int $code): void
abort(int $code, string $message): void
cookie(string $key, $default): mixed
setCookie(string $key, $value, int $expires, array $options): void
dump($var): void
dd($var): never
```

**App helpers (`App/helpers/functions.php`):**
```php
tenantContext(?Service_TenantContext $set = null): ?Service_TenantContext
validationErrMsg(string $key, string $field): string
isValidEmail($value): bool
isValidPrice($value): bool
isNonNegativeNumeric($value): bool
isPositiveNumeric($value): bool
formatMySqlDate(?string $date, ?string $format, string $fallback): string
unformatNumber($value): float
formatCurrency($value, array $options): string
formatPrice($value, array $options): string
formatQty($qty): string
normalizeIndianPhone($mobile): ?string
getCountries(): array
getCurrencies(): array
getTimezones(): array
methodNotAllowed(): TinyPHP_Response
```

---

## Frontend Assets (`Public/assets/`)

**Do not reinvent what already exists.** Before writing any JS, check `Public/assets/js/app-custom.js` and `Public/assets/js/app-datatable.js`.

### Key helpers in `app-custom.js`

| Helper | Purpose |
|--------|---------|
| `initSelect2(selector, options)` | Init/re-init Select2. Options: `dropdownParent`, `onChange`, `data`, `autoSelectSingle`, `resetVal` |
| `initDatePicker(selector, options)` | Flatpickr date picker with system date format |
| `datePickerSetDate(selector, date)` | Set date on existing Flatpickr instance |
| `initTimePicker(selector, options)` | Flatpickr time picker (24hr storage, 12hr display) |
| `timePickerSetTime(selector, value)` | Set time on existing Flatpickr instance |
| `buildSelect2Options(data, config)` | Convert API arrays to `{id, text}` Select2 options |
| `buildCategorySelect2Options(categories, level)` | Hierarchical category options |
| `formatMySqlDate(date, format, fallback)` | Format MySQL DATE/DATETIME for display |
| `formatCurrency(value, options)` | Format as currency |
| `formatPrice(value, options)` | Format price |
| `formatQty(qty)` | Format quantity |
| `unformatNumber(value)` | Strip number formatting |
| `handleApiError(error, formElement)` | Unified API error handler (401→login redirect, 403→denied, 422→field errors) |
| `showFormInputFeedback(input, message, type)` | Per-field feedback |
| `showFormGlobalFeedback(formEl, message, type)` | Form-level feedback |
| `cleanFormInputFeedback(formEl)` | Clear all feedback |
| `showConfirmation(message, type, confirmObj, cancelObj)` | Confirmation dialog |
| `formDataToObject(formData)` | FormData → plain object |
| `splitDateTime(dateTime)` | MySQL datetime → `{date, time}` |
| `extractSelect2OptionValue(item, key)` | Safely get value from Select2 option |
| `populateDropzoneImage(instance, imageUrl)` | Pre-populate Dropzone with existing image |
| `getDropzoneInstance(selector)` | Get Dropzone instance from element |
| `readFilesAsBase64(fileInput)` | Read `<input type="file">` as base64 `{name, mime_type, content}[]` |
| `readDropzoneFilesAsBase64(dzInstance)` | Read new Dropzone files as base64 |
| `downloadAttachment(url, filename)` | Fetch protected attachment and trigger download |
| `custDebounce(fn, delay)` | Debounce helper |

**Global constants:**
```javascript
window.notyf = new Notyf()   // notification system
UNAUTHORIZED_MESSAGE
ACCESS_DENIED_MESSAGE
DELETE_CONFIRM_MESSAGE
```

### Key helpers in `app-datatable.js`

| Helper | Purpose |
|--------|---------|
| `initDataTable(selector, userOptions)` | Init DataTable with project defaults (responsive, server-side). Always use instead of `new DataTable(...)` |
| `mapApiToDataTable(json)` | Map API JSON to DataTable expected format |

---

## Drawer JS Patterns

Drawers are slide-in modal panels for create/edit forms. Standard patterns:

- `buildDisplayNameOptions()` — builds display name options from current form state
- `refreshDisplayNameSelect(forceSelect = null)` — destroys and reinits display name Select2; pass stored `display_name` string when editing
- Debounced refresh on company_name, first_name, last_name input and salutation change: use `custDebounce(() => refreshDisplayNameSelect(), 300)` — **do not** pass the function reference directly (debounce wrapper forwards the DOM event as `forceSelect`)
- `populateForm(details)` — sets all fields from API response; set Select2 values via jQuery `.val().trigger('change')`; call `refreshDisplayNameSelect(display_name)` last
- `openFormDrawer(id)` — reset form, reinit all Select2s, load form-context, populate if editing

---

## Important Constraints & Decisions

1. **No migration runner** — All DB changes go in `Updates/db_changes.sql`, applied manually.
2. **No raw Eloquent** — Use `TinyPHP_ActiveRecord` model methods or the `DB()` query builder. Never call Laravel Eloquent methods directly.
3. **No automated tests** — Test manually by running the app.
4. **No npm/Node** — Pure PHP app. Do not introduce Node build tools.
5. **Multi-tenancy is per-query** — Every query must be scoped by `company_id`. Get it from `$this->context->companyId` in services, or `tenantContext()->companyId` elsewhere.
6. **Drawer pattern for forms** — Create/edit forms are Blade drawer components (`app/components/drawers/`). Follow this pattern for all new form UIs.
7. **Form context endpoints** — Every resource with a drawer needs an API route ending in `/form-context` that returns dropdown data. Always add one.
8. **Sequence numbers use row-level locking** — `FOR UPDATE` in `Service_Sequence` to prevent duplicate numbers under concurrent load.
9. **Validation messages** — Use `validationErrMsg($key, $field)` referencing keys from `App/config/errors.php`. Never use inline strings.
10. **Two DB connections** — `main_db` for all operational data (default), `platform_db` for companies/users/auth/modules/features/roles/subscriptions. Currently same physical DB but logically separated.
11. **CSRF** — Handled by `Middleware_Csrf` on the `app` module. API module uses JWT, no CSRF needed.
12. **All dropdowns use Select2** — Every `<select>` in drawer forms must be initialized with `initSelect2(selector, options)`. Always set `dropdownParent` to the drawer element. Never call `.select2()` directly. Static options inline; dynamic options via `buildSelect2Options(data, config)`.
13. **Access keys on every authenticated route** — Every route that requires feature gating must have an `access_key` matching a key in the `features` table. Routes without `access_key` are accessible to all authenticated users with an active subscription.
14. **Service_TenantContext hydration** — Middleware hydrates it once per request. In controllers, get it via `tenantContext()`. In services, use `$this->context`. Never instantiate `Service_TenantContext` inline inside a service action unless spawning a sub-service that needs a fresh context.
