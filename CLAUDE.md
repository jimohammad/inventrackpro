# CLAUDE.md

## Project Overview
Custom PHP/MySQL ERP for Iqbal Sons (multi-warehouse mobile and electronics business). No framework. Covers inventory, sales, purchases, returns, payments, party ledgers, and accounting. Hosted on Hostinger.

## Structure
- `index.php` - Main entrypoint (query-parameter router: `?page=...&action=...`)
- `api/index.php` - API entrypoint; handlers in `api/v1/*.php`
- `app/controllers/` - Controller classes
- `app/models/` - Data models (`BaseModel` provides CRUD helpers)
- `app/views/` - PHP view templates (wrapped by `app/views/layout.php`)
- `app/helpers/` - Auth, CSRF, utilities
- `config/` - App config (`app.php`) and database (`database.php`, reads `.env`)
- `assets/` - JS/CSS (Bootstrap, DataTables, Select2)
- `database/` - Schema and migration SQL files
- `wf/`, `wh2/` - Partial forks of the main app (may drift from root)

## Decision Priority
1. Security
2. Data integrity and accounting correctness
3. Transactional consistency and stock correctness
4. Performance
5. Style and refactoring preferences

## Context Navigation
- Architecture/discovery questions: run `/graphify query "your question"` first.
- Start navigation from `graphify-out/wiki/index.md`.
- Read `graphify-out/GRAPH_REPORT.md` before architecture answers.
- For known-file edits, read files directly.

## Development Commands
```bash
# Local dev server
php -S 127.0.0.1:8000 -t .

# Syntax check single file
php -l app/controllers/SalesController.php

# Syntax check all PHP files (PowerShell)
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }

# Rebuild graphify after structural changes
python3 -c "from graphify.watch import _rebuild_code; from pathlib import Path; _rebuild_code(Path('.'))"
```

## Core Conventions
- Database access via singleton PDO wrapper in `config/database.php` (helpers: `fetchOne`, `fetchAll`, `insert`, `execute`)
- Critical stock/payment/invoice operations use DB transactions and `FOR UPDATE` locking
- API endpoints use API key auth, CORS, and rate limiting (globals: `$db`, `$method`, `$keyPermissions`)
- Server-rendered HTML with CDN assets; global JS behavior in `assets/js/app.js`
- No automated test suite - validate with syntax checks and endpoint smoke tests

## Non-Negotiable Patterns
- Party balance queries must use directional `CASE WHEN payment_type` logic.
- Payment direction: receipts increase balance, payments decrease.
- If touching discount `ref_type`, search all `ref_type` usages.
- Returns must reverse the original stock movement and respect warehouse location.
- All stock changes must stay inside transactions with row-level locking.
- Hidden inputs must be inside `<form>` tags to be submitted.
- Use vanilla JS `addEventListener` for events; do not add inline handlers (`onclick`, `onchange`, etc.).
- Table search and dynamic UI behavior live in `assets/js/app.js`.
- Avoid legacy MySQL session patterns that are incompatible with MySQL 8.0.
- Validate schema changes against the deployed DB before execution, not just `database/*.sql`.

## Security Requirements (Non-Negotiable)
- Validate CSRF tokens on all state-changing requests (POST/PUT/DELETE).
- Regenerate session ID on login to prevent session fixation.
- `.git`, `.env`, `.user`, and `user.ini` must not be web-accessible; verify `.htaccess` protections.
- API keys must stay in `.env` and must never be committed.
- Sanitize and type-validate user input even when using PDO prepared statements.
- Escape all output rendered as HTML.
- Public stock API (`iqbal.app/api/stock.php`) requires API key header and rate limiting.

## Performance
- Prefer JOINs over correlated subqueries unless profiling proves otherwise.
- Check existing indexes before adding new ones.
- OPcache and GZIP are enabled on Hostinger.
- Cloudflare was previously removed due to caching issues; do not reintroduce it without discussion.

## Change Completion Checklist
Before considering a change complete:
1. Run `php -l` on every modified PHP file.
2. If change touches discount, payment, or stock logic, search related references (for example all `ref_type` usages).
3. Check whether matching changes are also needed in `wf/` or `wh2/`.
4. For schema changes, validate against the deployed DB.
5. After structural changes, rebuild graphify.
6. Smoke-test the affected endpoint manually.

## Don't Do This
- Do not use legacy `mysql_*` functions (PDO only).
- Do not echo unescaped user input.
- Do not put hidden inputs outside `<form>` tags.
- Do not use inline event handlers (`onclick`, `onchange`); use `addEventListener`.
- Do not run `ALTER TABLE` without checking the deployed DB first.
- Do not commit `.env` or anything in `.user`.
- Do not bypass the singleton wrapper with raw `new PDO(...)`.

## Caveats
- `wf/` and `wh2/` are not clean aliases of the root app; verify behavior in each subtree.
- Schema SQL and runtime SQL may drift; deployed DB is the source of truth.
- Some controllers may not fully follow the `BaseModel` pattern; do not assume consistency.
