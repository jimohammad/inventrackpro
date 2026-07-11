# Accounts, landed cost & import payables

## Decisions

- **Account balance** = opening + payments in − payments out − expenses ± transfers ± adjustments.
- Discount payments (`ref_type = 'discount'`) are excluded from account balance rollups.
- Import logistics adds **landed cost** to purchase invoice unit costs when shipment is received.
- Freight/partner legs create **open accruals** until paid from Payments.

## Rules

### Account balance

- Use `AccountBalanceService` for reconciliation logic — do not duplicate formulas.
- `payment_type = 'in'` increases account; `out` decreases.
- PO advance: active `purchase_order` payment on non-cancelled PO counts toward account outflow.
- Cancelled PO/purchase must not leave active orphan payments.

### Import logistics cost legs (per item line)

| Field | Leg |
|-------|-----|
| `freight_hk_dxb` | HK → Dubai |
| `packing_dxb` | Packing in Dubai |
| `freight_dxb_kwt` | Dubai → Kuwait |
| `partner_profit_per_pc` | Fixed partner per-piece profit (default **0.250** KWD — `IMPORT_PARTNER_PROFIT_PER_PC`) |

Import shipment form (`landed_cost_form.php`): **Logiverse** and **Hi-IQ lump sum** fields divide a total invoice amount by combined line qty → same `/pc` on every row (last row absorbs rounding).

### Shipment receive

1. `LandedCostAllocator` applies charges to purchase lines / stock cost.
2. `ImportPayableAccrualService::createFromShipment()` opens accruals for unpaid legs.
3. Payables UI: `landed_cost_payables.php`, `landed_cost_partner_due.php` — monthly **one payment** per month; duplicate same-day `shipment_partner` rows (old bulk bug) are flagged with **Remove duplicates** / **Link to lump payment** (e.g. old PAY-000567 `ref_type=purchase`).

### Item master real cost

- `items.purchase_price` is the **real landed unit cost** in Item Master, stock reports, and COGS fallback.
- `ItemCostService` syncs it from the **latest non-cancelled** `purchase_items.unit_price` (after import receive that line already includes freight legs + partner profit per pc).
- Auto-sync on: direct purchase, PO convert, import receive, purchase edit, purchase cancel.
- Admin backfill: **Items → Sync Real Costs**.

### Net worth

- `NetWorthService::accountsAsOf()` and `cashAsOf()` delegate to `AccountBalanceService::computeFromLedgerAsOf()` (rebuild from opening, not rewind from stale `current_balance`).
- Balance sheet UI uses `NetWorthService::balanceSheetBundle()` (single pass). **PO prepayments** (`paid_kwd` on open POs in `paid`/`draft` status) are a **prepaid current asset** — cash is already reduced by those payments, so they are included in `total_assets` alongside cash, receivables, and inventory.
- Recorded month-end snapshots (`net_worth_snapshots`) are branch-scoped via `warehouse_id`; trend prefers stored rows over live reconstruction.

### Config

- Import partner / forwarder defaults in `config/app.php` (`IMPORT_FREIGHT_*` constants).

## Code map

| Area | Location |
|------|----------|
| Account settings / audit UI | `app/controllers/AccountController.php` |
| Balance service | `app/services/AccountBalanceService.php` — `computeFromLedgerAsOf()`, `reconciliationRows()`, `sqlPaymentLedgerWhere()` |
| Dashboard / Net worth / Balance sheet | `DashboardController`, `NetWorthService`, `ReportController::balanceSheet()` — cash via `AccountBalanceService` |
| Landed cost controller | `app/controllers/LandedCostController.php` |
| Allocator | `app/services/LandedCostAllocator.php` |
| Payment linker | `app/services/LandedCostPaymentLinker.php` |
| Accruals | `app/services/ImportPayableAccrualService.php` |
| Item real cost sync | `app/services/ItemCostService.php` — latest purchase line → `items.purchase_price` |
| Partner profit report | `ReportController::partnerProfit()` — `?page=reports&action=partnerProfit` |
| Diagnostic tool | `tools/diag_account9.php`, `tools/fix_account9_balance.php`, `tools/repair_partner_double_pay.php` |

## Verify

- **Settings → Accounts → Recalculate** — compare `current_balance` to the ledger total shown after recalc.
- CLI diagnostic for PO-conversion double-deductions: `php -d extension=pdo_mysql tools/diag_account9.php`
- Manual repair (use with care): `php -d extension=pdo_mysql tools/fix_account9_balance.php`

When investigating a specific account, compare `accounts.current_balance` to `AccountBalanceService::computeFromLedger()` (branch scope `NULL` matches Settings recalc).

## History

- **2026-07-07:** PO prepayments on open purchase orders count as **Prepaid Supplier Advances** in balance sheet `total_assets` (cash down + prepaid asset up — net worth unchanged until goods received).
- **2026-07-07:** Item master `purchase_price` auto-syncs from latest purchase line via `ItemCostService` (incl. full landed logistics + partner profit). Admin bulk sync on Items list.
- **2026-07-06:** Balance sheet category subtotals tie to `total_assets` (cash + receivables + inventory + PO prepayments); snapshots stored/read per `warehouse_id` — run `database/migrations/2026_07_06_networth_snapshots_warehouse.sql` on deployed DB.
- **2026-07-05:** Partner due detects duplicate same-day `shipment_partner` payments vs unlinked lump `purchase` pay; reconcile restores Partner due or links lines to existing lump without extra cash.
- **2026-06-11:** Audit SQL aligned with `AccountBalanceService` (payment cancel guards, PO unlinked, blank-ref duplicates, optional `@warehouse_id`).
- **2026-06-11:** Unified account balance formula — `computeFromLedgerAsOf()` powers Reconciliation, Account Statement, and Balance Sheet cash; branch reports use `(warehouse_id = ? OR warehouse_id IS NULL)`. Recalculate cleanup voids blank `ref_type` duplicate outbound payments.
