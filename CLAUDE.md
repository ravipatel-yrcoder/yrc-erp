# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Opsify** is a multi-tenant SaaS for inventory and purchasing management built on **TinyPHP** — a custom lightweight PHP MVC framework inspired by Laravel, located in `/TinyPHP/`.

## Running the Application

This is a PHP/Apache application. No build step required.

- **Web server:** Apache with mod_rewrite (`.htaccess` in `/Public/` routes everything to `index.php`)
- **Entry point:** `Public/index.php`
- **Dependencies:** `composer install` (uses Composer, not npm)
- **Environment:** Copy `.env.example` to `.env` and configure DB, JWT, and mail settings

There are no automated tests. Development is done by running the app locally via Apache/PHP.

## Architecture

### Framework: TinyPHP

Custom MVC framework in `/TinyPHP/`. Key classes:
- `Front.php` — bootstrapper, registers modules, runs middleware pipeline
- `Router.php` — route matching and dispatching
- `ActiveRecord.php` — ORM base class (wraps Laravel Illuminate Database)
- `Controller.php` — base controller with view rendering helpers
- `Request.php` / `Response.php` — HTTP abstractions
- `BladeRenderer.php` — Laravel Blade template engine integration

### Request Lifecycle

1. Apache rewrites all requests → `Public/index.php`
2. `App/bootstrap/app.php` loads config and initializes the framework
3. `Front::run()` resolves the module (app or api), runs middleware stack, dispatches to controller

### Module System

Two modules defined in `App/config/modules.php`:
- **app** — Server-side rendered Blade pages (routes in `App/routes/app.php`, controllers in `App/http/app/controllers/`)
- **api** — JSON API (routes in `App/routes/api.php`, controllers in `App/http/api/controllers/`)

### Authentication

- **App module:** Session-based auth via `AppAuth` middleware
- **API module:** JWT-based via `ApiAuth` middleware (config in `App/config/jwt.php`)
- Auth services: `App/service/Auth.php`, `App/service/AuthToken.php`

### Database

- **ORM:** Laravel Illuminate Database via `ActiveRecord` base class
- **Primary DB:** `main_db` connection — all operational data
- **Reporting DB:** `mysql_reporting` — secondary connection for analytics
- **Multi-tenancy:** `TenantContext` and `TenantDBResolver` services resolve per-tenant DB
- **Schema changes:** Tracked manually in `/Updates/db_changes.sql` (no migration runner)
- Models live in `App/models/`

### Service Layer

Business logic is in `App/service/`:
- `Po/Order.php`, `Po/Grn.php` — Purchase Order and Goods Received Note logic
- `Inv/Movement.php`, `Inv/Sequence.php` — Inventory stock movements and numbering
- `Product.php`, `Vendor.php` — Domain services
- `TenantContext.php` — Multi-tenancy resolution

### Views

Blade templates in `App/resources/views/`:
- Layouts: `app.blade.php` (authenticated), `front.blade.php` (public)
- Drawer components (e.g. `_drawer-product-form.blade.php`) are modal slide-in panels used for create/edit forms
- Data flows from controller → view via `$data` array passed to `$this->render()`

## Key Conventions

- **Controllers** extend `TinyPHP\Controller` and return `$this->render('view.name', $data)` for HTML or `$this->json($data)` for API responses
- **Models** extend `TinyPHP\ActiveRecord` and use Eloquent-style query builders. The static `$table` and `$connection` properties define the model's DB target
- **Routes** use `Router::get/post/put/delete($path, [ControllerClass::class, 'method'])` syntax
- **Middleware** classes implement a `handle(Request $request, callable $next)` method
- **CSRF** is handled by the `Csrf` middleware on app module POST routes; API uses JWT so no CSRF
- **Sequence numbers** (PO numbers, GRN numbers) are generated via `App/service/Inv/Sequence.php` using the `sequences` table with configurable patterns
