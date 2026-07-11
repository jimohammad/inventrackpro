# Warehouse isolation (Main vs Fahaheel)

## Decisions

- **Main Branch** (`warehouse_id = 1`) is the only operational entity (accounts, stock, sales, parties).
- **Fahaheel Branch** (`warehouse_id = 3`) is legal paperwork only — not used in day-to-day accounts. Keep `is_active = 0`.
- Branches are **fully independent** unless the business explicitly opens a second operational branch.
- The only intentional cross-branch feature is **Stock Transfers** (`stock_transfers`) — unused while Fahaheel is inactive.

## Rules

### Session

- Every logged-in page (except login/logout/warehouse picker/public links) uses `Auth::warehouseId()`.
- All new documents must save `warehouse_id` from the active session.

### Queries

- List/detail/report SQL must filter `warehouse_id = ?` (session) on: `sales`, `purchases`, `payments`, `returns`, `expenses`, `stock`, and branch-scoped aggregates.
- Party balance and statement use `Party::resolveScopeWarehouseId()` / `WarehouseScope`.
- Party `opening_balance` applies only when `parties.warehouse_id` is NULL or equals the active branch (`WarehouseScope::openingBalanceForParty`).
- `customer_discounts` has no `warehouse_id` — do not mix into branch opening; discount **payments** are branch-tagged via `payments.warehouse_id`.

### Public statement links

- Scope to the party’s home `warehouse_id` or dominant branch from sales — **never** merge both branches on one link.

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
UNION ALL SELECT 'stock', COUNT(*) FROM stock WHERE warehouse_id = @inactive_wh;
```

## History

- **2026-07-11 (security):** Sale returns force session `warehouse_id` (posted branch ignored if mismatched).
- **2026-07-11 (fix):** `SalesController` forces session `warehouse_id` on create; detail/print/pay/cancel/reopen/edit/add-item/IMEI-scan assert active branch; item search ignores client-posted warehouse.
- **2026-07-02 (fix):** Expenses branch-scoping — `ExpenseController::edit()/update()` and `Expense::delete()` now require `warehouse_id = Auth::warehouseId()` on the expense lookup (previously id-only, allowing cross-branch edit/delete and wrong-branch account balance reversal). `Expense::getSummaryByCategory()` now filters by session warehouse like `getAll()`. Also made the multi-row expense save atomic (single transaction) and added `amount > 0` validation on update.

_(Add dated notes when fixing branch-related bugs.)_
