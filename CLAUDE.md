# CLAUDE.md

## Project Overview
Custom PHP ERP application (no framework). Handles inventory, sales, purchases, returns, payments, and party ledger management.

## Structure
- `index.php` - Main entrypoint (query-parameter router: `?page=...&action=...`)
- `api/index.php` - API entrypoint; handlers in `api/v1/*.php`
- `app/controllers/` - Controller classes
- `app/models/` - Data models (BaseModel provides CRUD helpers)
- `app/views/` - PHP view templates (wrapped by `app/views/layout.php`)
- `app/helpers/` - Auth, CSRF, utilities
- `config/` - App config (`app.php`) and database (`database.php`, reads `.env`)
- `assets/` - JS/CSS (Bootstrap, DataTables, Select2)
- `database/` - Schema and migration SQL files
- `wf/`, `wh2/` - Partial forks of the main app (may drift from root)

## Context Navigation
When you need to understand the codebase, docs, or any files in this project:
1. ALWAYS query the knowledge graph first: `/graphify query "your question"`
2. Only read raw files if I explicitly say "read the file" or "look at the raw file"
3. Use `graphify-out/wiki/index.md` as your navigation entrypoint for browsing structure

## Development Commands
```bash
# Local dev server
php -S 127.0.0.1:8000 -t .

# Syntax check single file
php -l app/controllers/SalesController.php

# Syntax check all PHP files (PowerShell)
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

## Key Conventions
- Database access via singleton PDO wrapper in `config/database.php` (helpers: `fetchOne`, `fetchAll`, `insert`, `execute`)
- Critical stock/payment/invoice operations use DB transactions and `FOR UPDATE` locking
- No automated test suite - validate with syntax checks and endpoint smoke tests
- API endpoints use API key auth, CORS, and rate limiting (globals: `$db`, `$method`, `$keyPermissions`)
- Server-rendered HTML with CDN assets; global JS behavior in `assets/js/app.js`

## Caveats
- `wf/` and `wh2/` are not clean aliases of the root app - verify behavior in each subtree
- Schema SQL and runtime SQL may not be perfectly aligned - validate against deployed DB before schema edits

## graphify

This project has a graphify knowledge graph at graphify-out/.

Rules:
- Before answering architecture or codebase questions, read graphify-out/GRAPH_REPORT.md for god nodes and community structure
- If graphify-out/wiki/index.md exists, navigate it instead of reading raw files
- After modifying code files in this session, run `python3 -c "from graphify.watch import _rebuild_code; from pathlib import Path; _rebuild_code(Path('.'))"` to keep the graph current
