# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

---

## Project Overview

**Opsify** is a multi-tenant SaaS for inventory and purchasing management (with a sales module in progress). Built on **TinyPHP** — a custom lightweight PHP MVC framework inspired by Laravel, located in `/TinyPHP/`.

**Current branch:** `crm-module` — CRM leads, stages, activities.

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

## Module System

Two modules defined in `App/config/modules.php`:

| Module | Purpose | Routes | Controllers |
|--------|---------|--------|-------------|
| `app` | Server-side rendered Blade pages | `App/routes/app.php` | `App/http/app/controllers/` |
| `api` | JSON REST API | `App/routes/api.php` | `App/http/api/controllers/` |

---

## Routes

### Web Routes (`App/routes/app.php`)
### API Routes (`App/routes/api.php`)

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

---

## Service Layer

All services extend `Service_Base` which provides tenant context, validation helpers, and error collection.

## Middleware

Registered in `App/config/middlewares.php`:
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

---

## TinyPHP Framework (`/TinyPHP/`)

Custom MVC framework ~30 classes. Do not modify unless extending framework capabilities.

---

---

## Frontend Assets

Located in `Public/assets/`:

### JS Helper Functions — Always Use These

**Do not reinvent what already exists.** Before writing any frontend JS, check `Public/assets/js/app-custom.js` and `Public/assets/js/app-datatable.js` for a helper that covers the use case.

Key helpers in `app-custom.js`:

| Helper | Purpose |
|--------|---------|
| `initSelect2(selector, options)` | Initialize (or re-initialize) a Select2 dropdown. Handles destroy/reinit, `dropdownParent`, `onChange`, `data`, `autoSelectSingle`, `resetVal`. |
| `initDatePicker(selector, options)` | Flatpickr date picker with system date format applied automatically. |
| `datePickerSetDate(selector, date)` | Set a date on an existing Flatpickr instance (ISO date string). |
| `initTimePicker(selector, options)` | Flatpickr time picker (24hr storage, 12hr display). |
| `timePickerSetTime(selector, value)` | Set time on an existing Flatpickr instance (H:i value). |
| `buildSelect2Options(data, config)` | Convert API response arrays into Select2-compatible `{id, text}` option arrays. |
| `buildCategorySelect2Options(categories, level)` | Build hierarchical category options for Select2. |
| `formatMySqlDate(date, format, fallback)` | Format MySQL DATE or DATETIME strings for display using system date format. |
| `formatCurrency(value, options)` | Format a number as currency. |
| `formatPrice(value, options)` | Format a price value. |
| `formatQty(qty)` | Format a quantity. |
| `unformatNumber(value)` | Strip formatting from a number string. |
| `handleApiError(error, formElement)` | Display API validation errors on form fields. |
| `showFormInputFeedback(input, message, type)` | Show per-field feedback. |
| `showFormGlobalFeedback(formEl, message, type)` | Show form-level feedback message. |
| `cleanFormInputFeedback(formEl)` | Clear all field/form feedback. |
| `showConfirmation(message, type, confirmObj, cancelObj)` | Show a confirmation dialog. |
| `formDataToObject(formData)` | Convert `FormData` to a plain object. |
| `splitDateTime(dateTime)` | Split a MySQL datetime string into `{date, time}` parts. |
| `extractSelect2OptionValue(item, key)` | Safely extract a value from a Select2 option object. |
| `populateDropzoneImage(instance, imageUrl)` | Pre-populate a Dropzone instance with an existing image. |
| `getDropzoneInstance(selector)` | Get the Dropzone instance attached to an element. |
| `readFilesAsBase64(fileInput)` | Read files from an `<input type="file">` as base64 objects `{name, mime_type, content}`. Returns a Promise array. |
| `readDropzoneFilesAsBase64(dzInstance)` | Read new (non-existing) files from a Dropzone instance as base64 objects. Use this instead of `readFilesAsBase64` when the input is a Dropzone. |
| `downloadAttachment(url, filename)` | Fetch a protected attachment via credentialed request and trigger browser download. Shows `notyf.error` on 404 or failure. |

Key helpers in `app-datatable.js`:

| Helper | Purpose |
|--------|---------|
| `initDataTable(selector, userOptions)` | Initialize a DataTable with project defaults (responsive, server-side, etc.). Always use this instead of `new DataTable(...)` directly. |
| `mapApiToDataTable(json)` | Map API JSON response format to the DataTable expected format. |

## Database Schema

Schema is tracked manually in `Updates/db_changes.sql`. Apply changes manually to your local DB.

---

## Important Constraints & Decisions

1. **No migration runner** — All DB changes go in `Updates/db_changes.sql` and are applied manually.
2. **No raw Eloquent** — Do not call Laravel Eloquent methods directly. Always use `TinyPHP\ActiveRecord` (model methods) or the `DB` query builder class. When in doubt, read an existing service (e.g. `App/service/Crm/Lead.php`) or controller to see the correct pattern — consistency matters more than clever.
3. **No automated tests** — Test manually by running the app.
4. **No npm/Node** — Pure PHP app. Do not introduce Node build tools.
5. **Multi-tenancy is per-query** — Every query must be scoped by `company_id`. `Service_TenantContext` carries the current tenant identifiers; all service methods receive it as a parameter.
6. **Drawer pattern for forms** — Create/edit forms are implemented as Blade drawer components (`app/components/drawers/`) included in the parent page. They open as slide-in panels triggered by JS. Follow this pattern for new form UIs.
7. **Form context endpoints** — API routes ending in `/form-context` return dropdown data and configuration needed to render add/edit forms. Always add one when creating a new resource with a drawer.
8. **Sequence numbers use row-level locking** — `FOR UPDATE` in `Service_Sequence` and `Service_Inv_Sequence` to prevent duplicate numbers under concurrent load.
9. **Validation messages** — Use keys from `App/config/errors.php`, not inline strings.
10. **Two DB connections** — `main_db` for operational data (all models by default), `mysql_reporting` for analytics only.
11. **CSRF** — Handled by `Middleware_Csrf` on the `app` module. API module uses JWT, no CSRF needed.
12. **All dropdowns use Select2** — Every `<select>` in drawer forms must be initialized as Select2 with `dropdownParent` set to the drawer element. Use `initSelect2(selector, options)` from `app-custom.js` — never call `.select2()` directly. Static-option selects pass options inline; dynamic ones use `buildSelect2Options(data, config)`.

---

### Drawer JS Patterns

- `buildDisplayNameOptions()` — builds the array of display name options from current form state.
- `refreshDisplayNameSelect(forceSelect = null)` — destroys and reinitializes the display name Select2 with fresh options. Pass a stored `display_name` string when editing to restore the correct selection (falls back to manual entry if no match).
- Debounced refresh triggered on input to company_name, first_name, last_name, and change on salutation. Use `custDebounce(() => refreshDisplayNameSelect(), 300)` — **do not** pass the function reference directly as the debounce wrapper will forward the DOM event as `forceSelect`.
- `populateCustomerForm(details)` — sets all form fields from API response. Sets salutation via jQuery (`.val().trigger('change')`); calls `refreshDisplayNameSelect(display_name)` last.
- `openCustomerFormDrawer(id)` — resets form, reinitializes all Select2s, loads form-context, populates if editing.