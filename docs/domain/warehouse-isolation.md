# Warehouse isolation (Main vs Fahaheel)

## Decisions

- **Main Branch** (`warehouse_id = 1`) is the only operational entity (accounts, stock, sales, parties).
- **Fahaheel Branch** (`warehouse_id = 3`) is legal paperwork only — not used in day-to-day accounts. Keep `is_active = 0`.
- Branches are **fully independent** unless the business explicitly opens a second operational branch.
- The only intentional cross-branch feature is **Stock Transfers** (`stock_transfers`) — unused while Fahaheel is inactive.
- **UI:** `WAREHOUSE_UI_SWITCHER` in `config/app.php` is **false** — no header/menu “Switch Warehouse”; login auto-selects Main. Do not delete `warehouse_id` columns. Re-enable the switcher only if a second operational branch goes live.

## Rules

### Session

- Every logged-in page (except login/logout/warehouse picker/public links) uses `Auth::warehouseId()`.
- All new documents must save `warehouse_id` from the active session.

### Queries

- All operational queries scope `warehouse_id` to session branch.
- List/detail/report SQL must filter `warehouse_id = ?` (session) on: `sales`, `purchases`, `payments`, `returns`, `expenses`, `stock`, `device_dumps`, `sale_edit_requests`, and branch-scoped aggregates.
- Party balance and statement use `Party::resolveScopeWarehouseId()` / `WarehouseScope`.
- Party `opening_balance` applies only when `parties.warehouse_id` is NULL or equals the active branch (`WarehouseScope::openingBalanceForParty`).
- `customer_discounts` has no `warehouse_id` — do not mix into branch opening; discount **payments** are branch-tagged via `payments.warehouse_id`.

### Public statement / verification links

- Scope to the party’s home `warehouse_id` or dominant branch from sales — **never** merge both branches on one link.
- Public Manpower verify (`/v/{token}`) and PO document-pack verify (`/d/{token}`) re-query only the **signed** `warehouse_id` in the token.

### Do not

- Add “All warehouses” filters on operational reports.
- Use company-wide party balance in branch UI (`Party::getByType` / `findWithBalance` are branch-scoped).
- Copy Fahaheel stock/sales into Main totals or vice versa.

## Code map

| Area | Location |
|------|----------|
| Session warehouse | `app/helpers/Auth.php` |
| Scope helper | `app/helpers/WarehouseScope.php` |
| Party balance scope | `app/models/Party.php` — `balanceTransactionWarehouseClause()` |
| Cursor rule (duplicate) | `.cursor/rules/warehouse-isolation.mdc` |

## Verify

Run on deployed DB (replace `@inactive_wh` with the unused branch id, e.g. `3` for Fahaheel). Every `cnt` must be `0`:

```sql
SET @inactive_wh = 3;

SELECT 'sales' AS t, COUNT(*) AS cnt FROM sales WHERE warehouse_id = @inactive_wh
UNION ALL SELECT 'purchases', COUNT(*) FROM purchases WHERE warehouse_id = @inactive_wh
UNION ALL SELECT 'payments', COUNT(*) FROM payments WHERE warehouse_id = @inactive_wh
UNION ALL SELECT 'returns', COUNT(*) FROM `returns` WHERE warehouse_id = @inactive_wh
UNION ALL SELECT 'expenses', COUNT(*) FROM expenses WHERE warehouse_id = @inactive_wh
UNION ALL SELECT 'stock', COUNT(*) FROM stock WHERE warehouse_id = @inactive_wh
UNION ALL SELECT 'employees', COUNT(*) FROM employees WHERE warehouse_id = @inactive_wh
UNION ALL SELECT 'device_dumps', COUNT(*) FROM device_dumps WHERE warehouse_id = @inactive_wh;
```

- **2026-09-09:** Login auto-selects Main without requiring the warehouse picker. If the warehouse list query fails, session still lands on Main (`id = 1`) so sign-in can reach the dashboard.
- **2026-08-30:** New Purchase page no longer shows a branch dropdown. `warehouse_id` is always the session branch (hidden field + server force). Header fields aligned like New Sale.
- **2026-08-30:** New Sale page no longer shows a branch dropdown. `warehouse_id` is always the session branch (hidden field + server force).
- **2026-08-29:** Sales list range Print/PDF (`bulkPrint`) removed. Per-invoice print/PDF on the sales page is unchanged.
- **2026-08-25 (security):** Admin voided-sales list and Include voided always filter `sales.warehouse_id` to the session branch (no company-wide cancelled invoices).
- **2026-08-25 (security):** API `POST /api/?endpoint=sales` requires a warehouse-scoped key (empty scope is 403, same as GET). Client `warehouse_id` must be in the key’s allowed list.
- **2026-08-24:** Public Manpower verification (`/v/{token}`) re-reads sales for the **signed** `warehouse_id` in the token only — never both branches.
- **2026-09-09:** Cash custody module retired; `cash_handovers` dropped. No longer in the inactive-branch zero check.
- **2026-08-22:** Cash custody handovers (`cash_handovers`) were branch-scoped. Removed 2026-09-09.
- **2026-07-11 (security):** Sale returns force session `warehouse_id` (posted branch ignored if mismatched).
- **2026-07-11 (fix):** `SalesController` forces session `warehouse_id` on create; detail/print/pay/cancel/reopen/edit/add-item/IMEI-scan assert active branch; item search ignores client-posted warehouse.
- **2026-07-02 (fix):** Expenses branch-scoping — `ExpenseController::edit()/update()` and `Expense::delete()` now require `warehouse_id = Auth::warehouseId()` on the expense lookup (previously id-only, allowing cross-branch edit/delete and wrong-branch account balance reversal). `Expense::getSummaryByCategory()` now filters by session warehouse like `getAll()`. Also made the multi-row expense save atomic (single transaction) and added `amount > 0` validation on update.

_(Add dated notes when fixing branch-related bugs.)_
