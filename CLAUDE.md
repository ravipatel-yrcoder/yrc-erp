# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

---

## Project Overview

**Opsify** is a multi-tenant SaaS for inventory and purchasing management (with a sales module in progress). Built on **TinyPHP** — a custom lightweight PHP MVC framework inspired by Laravel, located in `/TinyPHP/`.

**Current branch:** `sales-module` — Phase 1A adds sales orders and deliveries.

---

## Running the Application

This is a PHP/Apache application. **No build step, no npm.**

```bash
composer install          # Install PHP dependencies
cp .env.example .env      # Configure DB, JWT, mail settings
```

- **Web server:** Apache with mod_rewrite — `.htaccess` in `/Public/` routes everything to `index.php`
- **Entry point:** `Public/index.php`
- **No automated tests** — development is done by running the app locally via Apache/PHP

---

## Directory Structure

```
/
├── App/                          # Application code
│   ├── bootstrap/
│   │   ├── app.php               # Main bootstrapper (env, config, modules, middleware, routes)
│   │   └── routes.php            # Route mapping helper
│   ├── config/                   # Configuration files (see Config Files section)
│   ├── helpers/
│   │   ├── functions.php         # App-wide helper functions (validation, formatting, currency)
│   │   ├── ErrorLogger.php
│   │   ├── FileUpload.php
│   │   └── Mailer.php / PHPMailer/
│   ├── http/
│   │   ├── app/controllers/      # 14 web (Blade) controllers
│   │   ├── api/controllers/      # 12 JSON API controllers
│   │   └── middleware/           # 6 middleware classes
│   ├── models/                   # ~30 ActiveRecord models
│   ├── routes/
│   │   ├── app.php               # Web routes
│   │   └── api.php               # API routes
│   ├── service/                  # Business logic services
│   │   ├── Auth.php / AuthToken.php
│   │   ├── Base.php              # Base service (validation, error collection, tenant context)
│   │   ├── Exception.php         # Custom service exceptions with HTTP status + field errors
│   │   ├── Product.php / Vendor.php / Sequence.php
│   │   ├── TenantContext.php / TenantDBResolver.php
│   │   ├── Po/Order.php          # Purchase Order business logic
│   │   ├── Po/Grn.php            # Goods Received Note (purchase receipt) logic
│   │   ├── Inv/Movement.php      # Stock movement logging
│   │   └── Inv/Sequence.php      # Serial/lot number generation
│   ├── resources/views/          # Blade templates
│   │   ├── layouts/app.blade.php         # Authenticated layout
│   │   ├── layouts/front.blade.php       # Public layout
│   │   ├── app/                          # All page views
│   │   │   ├── auth/                     # login, register, forgot/reset password
│   │   │   ├── components/drawers/       # Slide-in panel forms (add/edit)
│   │   │   ├── dashboard/, front/
│   │   │   ├── inventory/, invproducts/, invsettings/
│   │   │   ├── locations/, prodcategories/, productmasters/, products/
│   │   │   ├── purchaseorders/, purchasereceipts/
│   │   │   └── users/, vendors/
│   │   ├── errors/               # 403, 404 pages
│   │   └── partial/              # Reusable partials (app, common, front)
│   ├── scripts/controllers/      # CLI scripts
│   └── storage/cache/            # View cache
├── Public/                       # Apache webroot
│   ├── index.php                 # Entry point
│   ├── .htaccess                 # Rewrites all requests to index.php
│   ├── assets/
│   │   ├── css/                  # Custom CSS
│   │   ├── js/                   # Page-specific JS (120+ files)
│   │   └── vendor/               # Bootstrap, jQuery, DataTables, Select2, Flatpickr, etc.
│   └── uploads/                  # User file uploads
├── TinyPHP/                      # Custom MVC framework (~30 classes)
├── Updates/
│   └── db_changes.sql            # Manual DB schema tracking (no migration runner)
├── composer.json
└── vendor/                       # Composer packages
```

---

## Request Lifecycle

1. Apache rewrites all requests → `Public/index.php`
2. `index.php` defines constants (`ROOT_PATH`, `APP_PATH`, `TINY_PHP_PATH`), loads autoloaders, helpers, and `Front.php`
3. `App/bootstrap/app.php` initializes the framework:
   - Loads `.env` via `TinyPHP_EnvLoader`
   - Registers exception handler
   - Loads all config files
   - Sets timezone and initializes session
   - Registers modules, middlewares, and routes
4. `$app->dispatch()` runs the middleware pipeline then dispatches to the matched controller action

---

## Module System

Two modules defined in `App/config/modules.php`:

| Module | Purpose | Routes | Controllers |
|--------|---------|--------|-------------|
| `app` | Server-side rendered Blade pages | `App/routes/app.php` | `App/http/app/controllers/` |
| `api` | JSON REST API | `App/routes/api.php` | `App/http/api/controllers/` |

---

## Routes

### Web Routes (`App/routes/app.php`)

| Group | Path(s) |
|-------|---------|
| front | `/`, `/about-us`, `/contact-us` |
| auth | `/login`, `/register`, `/forgot-password`, `/reset-password` |
| products | `/products` |
| prodcategories | `/product-categories` |
| invsettings | `/settings/inventory` |
| locations | `/company/locations` |
| inventory | `/inv/adjustments` |
| invproducts | `/inv/products/:id/stock-locations` |
| vendors | `/vendors` |
| purchaseorders | `/purchase-orders`, `/purchase-orders/:id` |
| purchasereceipts | `/purchase-receipts`, `/purchase-receipts/:id` |

### API Routes (`App/routes/api.php`)

| Group | Endpoints |
|-------|----------|
| auth | `POST /api/auth/refresh-token` |
| prodcategories | GET/POST `/api/product-categories`, `/api/product-categories/form-context`, GET/PUT/DELETE `/api/product-categories/:id` |
| productmasters | GET `/api/product-masters` |
| products | GET/POST `/api/products`, `/api/products/form-context`, GET/PUT/DELETE `/api/products/:id` |
| locations | GET/POST `/api/company/locations`, `/api/company/locations/form-context`, GET/PUT/DELETE `/api/company/locations/:id` |
| inventory | POST `/api/inv/adjustments` |
| invproducts | GET `/api/inv/products/:id/stock-locations`, POST `/api/inv/products/:id/stock/adjust`, GET `/api/inv/products/:id/stock/adjust/form-context`, GET `/api/inv/products/:id/serial-or-lot-numbers` |
| invsequence | POST `/api/inv/sequence/generate` |
| vendors | GET/POST `/api/vendors`, `/api/vendors/form-context`, GET/PUT/DELETE `/api/vendors/:id` |
| purchaseorders | GET/POST `/api/purchase-orders`, GET `/api/purchase-orders/form-context`, GET/PUT `/api/purchase-orders/:id`, PUT `/api/purchase-orders/:id/status`, GET `/api/purchase-orders/:id/receive/form-context`, GET `/api/purchase-orders/:id/history` |
| purchasereceipts | GET/POST `/api/purchase-receipts`, GET/PUT `/api/purchase-receipts/:id`, GET `/api/purchase-receipts/:id/form-context`, PUT `/api/purchase-receipts/:id/status`, GET `/api/purchase-receipts/:id/history` |

---

## Authentication

| Context | Mechanism |
|---------|-----------|
| Web (`app` module) | Session-based. `AppAuth` middleware validates `access_token` cookie, renews using `refresh_token` cookie. Unauthenticated → redirect to `/login`. |
| API (`api` module) | JWT Bearer token. `ApiAuth` middleware validates `Authorization: Bearer <token>` header. |

- Auth services: `App/service/Auth.php`, `App/service/AuthToken.php`
- JWT config: `App/config/jwt.php` — secret, TTL 60 min, refresh TTL 14 days, algo HS256
- `Service_Auth::user()` validates token and checks user + company active status
- Exempt from auth: front pages, auth pages (web); `auth::login`, `auth::refreshToken`, `auth::register` (API)

---

## Database

- **ORM:** Laravel Illuminate Database (Eloquent-style) wrapped by `TinyPHP_ActiveRecord`
- **Primary DB:** `main_db` connection — all operational data
- **Reporting DB:** `mysql_reporting` — secondary analytics connection
- **Multi-tenancy:** `Service_TenantContext` (companyId, userId) + `Service_TenantDBResolver`
- **Schema tracking:** Manual, in `/Updates/db_changes.sql` — **no migration runner**, apply manually
- **Models:** `App/models/`

---

## Models

All models extend `TinyPHP\ActiveRecord` (Eloquent-style). Key static properties:

```php
protected static $table = 'table_name';
protected static $connection = 'main_db'; // or 'mysql_reporting'
```

### Model Inventory

| Domain | Models |
|--------|--------|
| Users | `Models_User` |
| Vendors | `Models_Vendor`, `Models_VendorAddress`, `Models_VendorContact` |
| Products | `Models_Product`, `Models_ProductMaster`, `Models_ProdCategory`, `Models_ProductTax`, `Models_ProductUom`, `Models_Uom` |
| Purchase Orders | `Models_PurchaseOrder`, `Models_PurchaseOrderItem`, `Models_PurchaseOrderHistory` |
| Purchase Receipts (GRN) | `Models_PurchaseOrderGrn`, `Models_PurchaseOrderGrnItem`, `Models_PurchaseOrderGrnItemLot`, `Models_PurchaseOrderGrnItemSerial`, `Models_PurchaseOrderGrnMovement`, `Models_PurchaseOrderGrnHistory` |
| Inventory | `Models_InvProductStock`, `Models_InvAdjustment`, `Models_InvStockMovement`, `Models_InvSerial`, `Models_InvSerialStock`, `Models_InvSerialHistory`, `Models_InvSequencePatterm` |
| Reference | `Models_Location`, `Models_Tax`, `Models_PaymentTerm`, `Models_Sequence` |
| Auth | `Models_AuthToken` |

**Purchase Order statuses:** `draft`, `confirmed`, `partially_received`, `received`, `cancelled`
**GRN statuses:** `draft`, `in_transit`, `received`

---

## Service Layer

All services extend `Service_Base` which provides tenant context, validation helpers, and error collection.

| Service | Responsibility |
|---------|---------------|
| `Service_Auth` | Login, logout, get current user, company context |
| `Service_AuthToken` | Generate/validate/refresh JWT tokens |
| `Service_Po_Order` | PO create, validate, status updates, list, show, history |
| `Service_Po_Grn` | GRN/receipt create, edit, validate, receive, status, list, show, history |
| `Service_Inv_Movement` | Stock movement logging (FIFO/LIFO) |
| `Service_Inv_Sequence` | Serial/lot number generation with row-level locking (FOR UPDATE) |
| `Service_Sequence` | PO/PR numbering with configurable patterns (e.g. `PO{YYYY}{MM}{DDDDDD}`) |
| `Service_Product` | Product domain logic |
| `Service_Vendor` | Vendor domain logic |
| `Service_TenantContext` | Carries companyId + userId for multi-tenant context |
| `Service_TenantDBResolver` | Resolves tenant DB connections |
| `Service_Exception` | Custom exceptions with HTTP status code + field-level errors |

---

## Middleware

Registered in `App/config/middlewares.php`:

| Middleware | Scope | Purpose |
|-----------|-------|---------|
| `Middleware_Log` | Global | Request logging |
| `Middleware_AppAuth` | App module | Session auth, token renewal |
| `Middleware_Csrf` | App module | CSRF token validation on POST/PUT/PATCH/DELETE |
| `Middleware_AppRedirectIfAuth` | App module | Redirects logged-in users away from login/register |
| `Middleware_ApiAuth` | API module | JWT Bearer token validation |

All middleware implement `handle(Request $request, callable $next)`.

---

## Views

Blade templates in `App/resources/views/`.

- **Layouts:** `layouts/app.blade.php` (authenticated, includes sidebar/nav), `layouts/front.blade.php` (public)
- **Drawer components:** `app/components/drawers/**/add-edit.blade.php` — modal slide-in panels for create/edit forms. Each drawer is included in the parent page view and triggered via JS.
- **Data flow:** Controller builds `$data` array → `$this->render('view.name', $data)` → view uses `$data` variables directly
- **Partials:** `partial/app/` (sidebar, nav, footer), `partial/common/`, `partial/front/`

---

## Key Coding Conventions

### Controllers

```php
// Web controller — returns HTML
class PurchaseOrdersController extends TinyPHP\Controller {
    public function indexAction() {
        $data = [...];
        return $this->render('app.purchaseorders.index', $data);
    }
}

// API controller — returns JSON
class PurchaseOrdersController extends TinyPHP\Controller {
    public function indexAction() {
        return $this->json(['data' => $orders]);
    }
}
```

### Models

```php
class Models_PurchaseOrder extends TinyPHP\ActiveRecord {
    protected static $table = 'purchase_orders';
    protected static $connection = 'main_db';
}
```

### Routes

```php
Router::get('/purchase-orders', [PurchaseOrdersController::class, 'index']);
Router::post('/api/purchase-orders', [PurchaseOrdersController::class, 'store']);
// Param routes
Router::get('/purchase-orders/:id', [PurchaseOrdersController::class, 'edit']);
```

### Services

Services extend `Service_Base`, receive `TenantContext` for multi-tenancy, and throw `Service_Exception` on errors:

```php
class Service_Po_Order extends Service_Base {
    public function save(array $payload, TenantContext $ctx): array {
        // validate, persist, return result
    }
}
```

### Sequence Numbers

Use `Service_Sequence` for PO/PR numbers and `Service_Inv_Sequence` for serial/lot numbers. Patterns registered in `sequences` table (e.g., `PO{YYYY}{MM}{DDDDDD}`). Use row-level locking to prevent duplicates.

### API Error Responses

Field-level validation errors are returned as structured JSON. `Service_Exception` carries an HTTP status code and a `$errors` array with field-keyed messages from `App/config/errors.php`.

---

## Config Files

Located in `App/config/`:

| File | Purpose |
|------|---------|
| `app.php` | APP_NAME, APP_ENV, APP_DEBUG, APP_URL, timezone, locale |
| `database.php` | Two DB connections: `main_db` (primary), `mysql_reporting` (analytics) |
| `jwt.php` | JWT_SECRET, TTL (60 min), refresh TTL (14 days), algorithm (HS256) |
| `modules.php` | Registers `api` module path |
| `middlewares.php` | Global + per-module middleware registration |
| `cors.php` | CORS config for `api/*` paths |
| `hashing.php` | Password hashing (bcrypt) |
| `errors.php` | Validation error message strings |
| `sys_default.php` | Locale, currency, date/time formats |
| `constants.php` | Application constants |
| `countries.php` / `currencies.php` / `country_currency.php` | Reference data |

---

## TinyPHP Framework (`/TinyPHP/`)

Custom MVC framework ~30 classes. Do not modify unless extending framework capabilities.

| Class | Purpose |
|-------|---------|
| `Front.php` | Front controller singleton, module/middleware registration, request dispatch |
| `Router.php` | Route matching (static + regex) and registration |
| `Controller.php` | Base controller (render, json, page title, breadcrumbs) |
| `Request.php` | HTTP request wrapper (input, method, headers, CSRF) |
| `Response.php` | HTTP response wrapper (JSON, HTML, status codes) |
| `ActiveRecord.php` | ORM base class (Eloquent-style CRUD, relationships, lazy-loading) |
| `BladeRenderer.php` | Laravel Blade template engine integration |
| `DB.php` | Database wrapper around Illuminate Database |
| `Session.php` | Session management and CSRF token generation |
| `Jwt.php` | JWT generate/validate/refresh |
| `Hash.php` | Password hashing (bcrypt) |
| `MiddlewarePipeline.php` | Middleware chain execution |
| `EnvLoader.php` | `.env` file loading (vlucas/phpdotenv) |
| `DataTable.php` / `DataFetch.php` / `TableDataProvider.php` | DataTable utilities |
| `SQLCache.php` | Query result caching |
| `HandleCors.php` | CORS header management |
| `AppEvent.php` / `AppEventHandler.php` / `PluginBroker.php` | Event system |
| `Support/helpers.php` | Global helpers: `config()`, `auth()`, `DB()`, `response()`, `redirect()`, `cookie()` |

---

## PHP Dependencies (composer.json)

| Package | Version | Purpose |
|---------|---------|---------|
| `illuminate/database` | 11.42.0 | Eloquent ORM |
| `illuminate/view` | 11.45.0 | Blade templating |
| `illuminate/events` | 11.45.0 | Event system |
| `illuminate/support` | 11.45.0 | Support utilities |
| `firebase/php-jwt` | ^6.11 | JWT token handling |
| `vlucas/phpdotenv` | 5.6.* | `.env` file loading |
| `ezyang/htmlpurifier` | 4.18.* | HTML sanitization |
| `phpoption/phpoption` | 1.9.* | Option type |

---

## Frontend Assets

Located in `Public/assets/`:

- **Vendor JS/CSS:** Bootstrap, jQuery, DataTables, Select2, Flatpickr, SweetAlert2, Notyf, Dropzone, Tagify
- **Custom JS:** `app-axios.js` (HTTP client), `app-custom.js`, `app-datatable.js`, `main.js`, `config.js`
- **Page JS:** `Public/assets/js/` — 120+ page-specific JS files for each feature (datatables, drawers, form handling)
- **No build pipeline** — plain JS files served directly

---

## Database Schema

Schema is tracked manually in `Updates/db_changes.sql`. Apply changes manually to your local DB.

### Key Tables (existing)

| Table | Purpose |
|-------|---------|
| `companies` | Multi-tenant company records |
| `users` | Users per company |
| `sequences` | Numbering patterns (PO, GRN, etc.) |
| `vendors`, `vendor_addresses`, `vendor_contacts` | Vendor data |
| `products`, `product_masters`, `product_categories`, `product_taxes`, `product_uoms`, `uoms` | Product catalog |
| `purchase_orders`, `purchase_order_items`, `purchase_order_history` | PO data |
| `purchase_order_grns`, `purchase_order_grn_items`, `purchase_order_grn_item_lots`, `purchase_order_grn_item_serials`, `purchase_order_grn_movements`, `purchase_order_grn_history` | GRN/receipt data |
| `inv_product_stock`, `inv_stock_movements`, `inv_adjustments` | Inventory stock |
| `inv_serials`, `inv_serial_stock`, `inv_serial_history`, `inv_sequence_patterns` | Serial/lot tracking |
| `company_locations`, `payment_terms`, `taxes` | Reference data |
| `auth_tokens` | JWT token storage |

### Sales Module Tables (Phase 1A — current branch)

| Table | Purpose |
|-------|---------|
| `customer_groups` | Customer segments |
| `customers`, `customer_addresses`, `customer_contacts` | Customer data |
| `price_lists`, `price_list_items` | Pricing |
| `sales_orders`, `sales_order_items`, `sales_order_history` | SO data |
| `sales_deliveries`, `sales_delivery_items`, `sales_delivery_item_serials`, `sales_delivery_item_lots`, `sales_delivery_history` | Delivery/dispatch notes |

---

## Important Constraints & Decisions

1. **No migration runner** — All DB changes go in `Updates/db_changes.sql` and are applied manually.
2. **No automated tests** — Test manually by running the app.
3. **No npm/Node** — Pure PHP app. Do not introduce Node build tools.
4. **Multi-tenancy is per-query** — Every query must be scoped by `company_id`. `Service_TenantContext` carries the current tenant identifiers; all service methods receive it as a parameter.
5. **Drawer pattern for forms** — Create/edit forms are implemented as Blade drawer components (`app/components/drawers/`) included in the parent page. They open as slide-in panels triggered by JS. Follow this pattern for new form UIs.
6. **Form context endpoints** — API routes ending in `/form-context` return dropdown data and configuration needed to render add/edit forms. Always add one when creating a new resource with a drawer.
7. **Sequence numbers use row-level locking** — `FOR UPDATE` in `Service_Sequence` and `Service_Inv_Sequence` to prevent duplicate numbers under concurrent load.
8. **Validation messages** — Use keys from `App/config/errors.php`, not inline strings.
9. **Two DB connections** — `main_db` for operational data (all models by default), `mysql_reporting` for analytics only.
10. **CSRF** — Handled by `Middleware_Csrf` on the `app` module. API module uses JWT, no CSRF needed.
11. **All dropdowns use Select2** — Every `<select>` in drawer forms must be initialized as Select2 with `dropdownParent` set to the drawer element. Static-option selects (e.g. salutation) are initialized in `openCustomerFormDrawer`; dynamic-option selects (payment terms, customer groups) use `initSelect2(..., { data: buildSelect2Options(...) })`.

---

## Sales Module — Customers (Phase 1A, completed)

### Files

| File | Purpose |
|------|---------|
| `App/models/Customer.php` | ActiveRecord model for `customers` table |
| `App/models/CustomerAddress.php` | ActiveRecord model for `customer_addresses` |
| `App/models/CustomerContact.php` | ActiveRecord model for `customer_contacts` |
| `App/models/CustomerGroup.php` | ActiveRecord model for `customer_groups` |
| `App/service/Customer.php` | Business logic: create, update, checkDuplicate, getFormContext |
| `App/http/api/controllers/CustomersController.php` | API controller (index/create/update, formContext, checkDuplicate) |
| `App/http/app/controllers/CustomersController.php` | Web controller (index page) |
| `App/resources/views/app/customers/index.blade.php` | Customer list page with DataTable |
| `App/resources/views/app/components/drawers/customers/add-edit.blade.php` | Create/edit drawer |

### API Routes (in `App/routes/api.php`)

```
GET/POST  /api/customers                  → index (list + create/update)
GET       /api/customers/form-context     → formContext (dropdown data + customer details for edit)
GET       /api/customers/check-duplicate  → checkDuplicate (email/phone uniqueness check)
GET/POST  /api/customers/:id              → index (update by id)
```

Static routes (`form-context`, `check-duplicate`) **must be declared before** the param route (`:id`) to avoid the router treating them as IDs.

### Customer Name Design

- **customer_type**: `company` or `individual` (radio buttons)
- **Salutation / First name / Last name**: always visible — for company type these are the contact person (optional); for individual they describe the person. First name is always required.
- **Company name**: required for `company` type, optional for `individual` type. Label updates dynamically via JS.
- **Display name**: Select2 dropdown — options auto-generated from filled-in fields (e.g. `John Smith`, `Mr. John Smith`, `Smith, John`, `Acme Corp`, `John Smith (Acme Corp)`). Last option is `— Enter manually...` which reveals a text input pre-filled with the first auto-generated option. Value is sent via `<input type="hidden" name="display_name">`.

### Service Layer Notes (`Service_Customer`)

- `normalizePayload()` — sets `company_name`, `display_name` (user-provided or auto-generated from salutation/first/last), nullifies empty optional fields. For `individual` type, `company_name` is preserved as-is (contact's company) but not required.
- `validatePayload()` — `first_name` always required; `company_name` required only for `company` type.
- `checkDuplicate()` — raw SQL via `$this->db->fetchOne()` with whitelisted column name (`email` or `phone`). Returns `{ exists, customer }`.
- `saveAddresses()` — upserts billing and shipping addresses; skips if no fields filled.
- `getFormContext()` — returns `{ customerDetails, paymentTerms, customerGroups }`. Customer details include `billing_address` and `shipping_address` sub-arrays.

### Drawer JS Patterns

- `buildDisplayNameOptions()` — builds the array of display name options from current form state.
- `refreshDisplayNameSelect(forceSelect = null)` — destroys and reinitializes the display name Select2 with fresh options. Pass a stored `display_name` string when editing to restore the correct selection (falls back to manual entry if no match).
- Debounced refresh triggered on input to company_name, first_name, last_name, and change on salutation. Use `custDebounce(() => refreshDisplayNameSelect(), 300)` — **do not** pass the function reference directly as the debounce wrapper will forward the DOM event as `forceSelect`.
- `populateCustomerForm(details)` — sets all form fields from API response. Sets salutation via jQuery (`.val().trigger('change')`); calls `refreshDisplayNameSelect(display_name)` last.
- `openCustomerFormDrawer(id)` — resets form, reinitializes all Select2s, loads form-context, populates if editing.

### Duplicate Detection

- Checks email and phone on `blur` with 300ms debounce.
- Displays a non-blocking `<p>` warning (`#cust_email_dup_msg`, `#cust_phone_dup_msg`). User can still save if intentional.
- Passes `customer_id` to exclude the current record when editing.
