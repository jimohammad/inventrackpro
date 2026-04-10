# AGENTS.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project shape
- This is a custom PHP ERP application (no framework like Laravel/Symfony).
- Main web app entrypoint: `index.php` (query-parameter router: `?page=...&action=...`).
- API entrypoint: `api/index.php` with per-endpoint handlers in `api/v1/*.php`.
- Two additional app variants exist under `wf/` and `wh2/`; they are partial forks of the main app and can drift from root behavior.

## Common development commands
- Start local dev server from repository root:
  - `php -S 127.0.0.1:8000 -t .`
- Basic syntax check for one file (single-file “unit” check):
  - `php -l app/controllers/SalesController.php`
- Syntax check all PHP files:
  - `Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }`
- Initialize database schema:
  - `mysql -u <user> -p <database> < database/schema.sql`
- Apply performance indexes after schema import:
  - `mysql -u <user> -p <database> < database/add_indexes.sql`

## Testing status
- No automated PHPUnit/composer/npm test suite is configured in this repository.
- For targeted validation, use endpoint-level smoke tests plus syntax checks.
- API smoke test pattern (replace placeholders):
  - `Invoke-RestMethod -Method GET -Uri "http://127.0.0.1:8000/api/index.php?endpoint=products&page=1" -Headers @{ "X-API-KEY" = "{{API_KEY}}" }`

## Configuration and runtime assumptions
- Database credentials are loaded from `.env` by `config/database.php` (it checks multiple locations including one above web root).
- Key app constants (currency, prefixes, timezone, API rate limit) live in `config/app.php`.
- Session/auth and CSRF behavior are centralized in `app/helpers/Auth.php` and `app/controllers/BaseController.php`.
- `.htaccess` contains security/caching rules and denies direct access to sensitive folders/files.

## Big-picture architecture
1. Request routing and bootstrapping
- `index.php` loads config, auth helpers, base classes, enforces auth/warehouse selection, maps `page` to controller class, and dispatches `action`.
- Controllers are mostly thin orchestration layers that gather input, call model/database logic, and render views.

2. Data access model
- `config/database.php` defines a singleton `Database` wrapper around PDO with helper methods (`fetchOne`, `fetchAll`, `insert`, `execute`, transactions).
- `app/models/BaseModel.php` provides shared CRUD helpers and pagination scaffolding.
- Many domain flows still issue direct SQL from controllers/models instead of a strict repository/service split.

3. Core business domains
- Inventory domain: `items`, `stock`, `warehouses`, and IMEI tracking (`imei_records`, plus join tables for sale/purchase/return linkage).
- Commercial flows:
  - Purchases increase stock and may create outbound payments.
  - Sales decrease stock, optionally register inbound payments, and update invoice balances.
  - Returns adjust stock/IMEI state and rebalance original sale obligations.
- Finance domain: `payments`, `accounts`, `expenses`, landed costs, discounts, and reconciliation reports.
- Party ledger model is unified: customers/suppliers share `parties`; reporting computes net positions from sales, purchases, payments, and returns.

4. Transactional integrity hotspots
- Critical stock/payment/invoice number updates are guarded with DB transactions and `FOR UPDATE` patterns in models like:
  - `app/models/Sale.php`
  - `app/models/Payment.php`
  - `app/models/Return.php`
  - `app/controllers/PurchaseController.php` (for purchase/payment numbering in current implementation)
- When changing these flows, preserve atomic stock updates and account-balance side effects together.

5. UI and rendering
- Views are PHP templates in `app/views/**` wrapped by `app/views/layout.php`.
- Frontend is server-rendered HTML + CDN assets (Bootstrap/DataTables/Select2) with global behavior in `assets/js/app.js`.

6. API structure
- `api/index.php` enforces API key auth, CORS, and per-key rate limiting before loading `api/v1/<endpoint>.php`.
- Endpoint files rely on globals (`$db`, `$method`, `$keyPermissions`) set by API bootstrap; keep this contract when adding endpoints.

## Important repository-specific caveats
- `wf/` and `wh2/` are not clean aliases of root app; verify behavior in the specific subtree you edit.
- `database/schema.sql` and runtime SQL references are not perfectly aligned in all places (for example, some index/feature SQL references columns/tables not declared in the base schema). Validate against the actual deployed DB before schema edits.
- Dashboard global search URL generation currently uses invoice numbers in places where detail pages usually expect numeric IDs; verify existing route expectations before modifying search behavior.
