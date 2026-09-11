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
- PO advance: active `purchase_order` payment counts toward account outflow even if the PO is later cancelled (bank is not restored). Recalculate unlinks leftover rows to unallocated `purchase` credit (`ref_id = 0`) instead of voiding them.
- Cancelled **purchase invoices** still void linked PAY and restore cash (Purchase cancel). Do not treat cancelled PO the same way.
- **Admin ledger edit:** from Accounts → open a bank/cash card → select a transaction → **Edit selected** (or the row pencil). Admin-only (`Auth::isAdmin()` + PIN). Payments/expenses open the existing editors and return to the ledger; transfers/adjustments edit on the Accounts page. Blocked: discount PAY (Discounts module), posted PO advances, unlinked PO payouts.

### Import logistics cost legs (per item line)

| Field | Leg |
|-------|-----|
| `freight_hk_dxb` | HK → Dubai |
| `packing_dxb` | Packing in Dubai |
| `freight_dxb_kwt` | Dubai → Kuwait |
| `partner_profit_per_pc` | Fixed partner per-piece profit (default **0.250** KWD — `IMPORT_PARTNER_PROFIT_PER_PC`) |

Import shipment form (`landed_cost_form.php`): **HK~DXB** and **Hi-IQ lump sum** fields divide a total invoice amount by combined line qty → same `/pc` on every row (last row absorbs rounding).

HK→DXB forwarder is **not** a single permanent party. The HK column header is a dropdown: **Logix One FZE (26049)** (default) or **Logiverse FZCO (26045)**. That choice is stored on every charge line as `freight_hk_dxb_party_id`. Packing (Union Logistics) and DXB→KW (Hi-IQ) stay fixed — no party dropdown. Accruals follow the selected HK party. “Clear all Logix” still targets **26049** only.

### Shipment receive

1. `LandedCostAllocator` applies charges to purchase lines / stock cost.
2. `ImportPayableAccrualService::createFromShipment()` opens accruals for unpaid legs.
3. Payables UI: `landed_cost_payables.php` (immediate freight HK/DXB), `landed_cost_packing_due.php` (**Union Logistics packing — invoice AP**), `landed_cost_partner_due.php` (partner profit — monthly one payment). Freight groups one payment per forwarder + shipment + leg (Hi-IQ invoice total). Duplicate same-day `shipment_partner` rows (old bulk bug) are flagged with **Remove duplicates** / **Link to lump payment** (e.g. old PAY-000567 `ref_type=purchase`).

### Union packing — vendor bill AP (international practice)

| Layer | What | On party statement? |
|-------|------|---------------------|
| Shipment `packing_dxb` / IPA `leg=packing_dxb` | ERP **estimate** for landed cost / Packing due | **No** |
| `import_vendor_bills` | Vendor **invoice** amount | **Yes** (credit) |
| Payment `shipment_packing_dxb` | Cash / bank PAY | **Yes** (debit vs AP) |

- Settle from Packing due with **Union invoice KWD** (`payPackingBulk`) or **link existing PAY** (`linkPackingExisting`).
- Each settle creates a paid vendor bill (= PAY amount) and clears packing estimate accruals from AP scope.
- One-time admin: **Migrate → vendor bill AP** (`migrateUnionPackingToVendorBills`) for historical Union PAYs.
- Do **not** use opening-balance force-Clear for packing variance — invoice ↔ payment is the control.

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

- Import partner / forwarder defaults in `config/app.php` (`IMPORT_FREIGHT_*` / `IMPORT_PARTNER_*` constants).
- Partner profit party: `IMPORT_PARTNER_PARTY_CODE` = **26058** (`Muhammad Faisal ( Partner )`).
- HK→DXB default: `IMPORT_FREIGHT_HK_PARTY_CODE` = **26049** (Logix One FZE). Alternate: `IMPORT_FREIGHT_HK_ALT_PARTY_CODES` = **26045** (Logiverse FZCO).

## Code map

| Area | Location |
|------|----------|
| Account settings / audit UI | `app/controllers/AccountController.php` |
| Ledger transaction edit (admin) | Accounts ledger → payment/expense editors; `updateAdjustment()` for manual adjustments |
| Balance service | `app/services/AccountBalanceService.php` — `computeFromLedgerAsOf()`, `reconciliationRows()`, `sqlPaymentLedgerWhere()` |
| Dashboard / Net worth / Balance sheet | `DashboardController`, `NetWorthService`, `ReportController::balanceSheet()` — cash via `AccountBalanceService` |
| Landed cost controller | `app/controllers/LandedCostController.php` |
| Allocator | `app/services/LandedCostAllocator.php` |
| Payment linker | `app/services/LandedCostPaymentLinker.php` |
| Accruals | `app/services/ImportPayableAccrualService.php` |
| Packing vendor bills | `app/services/PackingVendorBillService.php` — `import_vendor_bills` |
| Item real cost sync | `app/services/ItemCostService.php` — latest purchase line → `items.purchase_price` |
| Partner profit report | `ReportController::partnerProfit()` — `?page=reports&action=partnerProfit` |
| Diagnostic tool | `tools/diag_account9.php`, `tools/fix_account9_balance.php`, `tools/repair_partner_double_pay.php` |

## Verify

- **Settings → Accounts → Recalculate** — compare `current_balance` to the ledger total shown after recalc.
- CLI diagnostic for PO-conversion double-deductions: `php -d extension=pdo_mysql tools/diag_account9.php`
- Manual repair (use with care): `php -d extension=pdo_mysql tools/fix_account9_balance.php`

When investigating a specific account, compare `accounts.current_balance` to `AccountBalanceService::computeFromLedger()` (branch scope `NULL` matches Settings recalc).

## History

- **2026-09-09:** Ledger account **Knet warba** renamed to **Knet - Warba Bank** (`AccountLedgerLoader::renameKnetWarbaAccount()`). Same account id and balances.

- **2026-09-09:** Cash Custody module and the auto-created **Custody** cash account were removed. Remaining custody balance (if any) is returned to **Main Cash** on first Accounts page load (`AccountLedgerLoader::retireCashCustodyModule()`). Do not recreate a Custody/safe account.

- **2026-08-29 (ledger review):** Cash formula no longer drops `purchase_order` PAY when the PO is cancelled (bank stays posted). Recalculate unlinks those rows to `purchase` / `ref_id = 0` instead of voiding. Dashboard account cards rebuild from `AccountBalanceService` (as-of today, session branch) instead of stale `current_balance`. Payment create/delete/edit lock the account row before patching the cache.

- **2026-08-25 (admin ledger edit PIN):** Accounts → bank/cash card → **Edit selected** asked for the admin PIN then stayed on the same page (`href="#"` after PIN). Button now opens the selected row’s editor after PIN; row pencils unchanged. PIN interceptor no longer treats `#` as a destination.
- **2026-08-23 (HK→DXB forwarder choice):** HK freight is no longer locked to Logix One FZE. On the shipment form, pick **Logix One FZE (26049)** or **Logiverse FZCO (26045)** from the party dropdown under the HK /pc amount (default 26049). Accruals post to the selected party.
- **2026-08-15 (admin ledger edit):** Accounts card ledger (bank/cash) lets admins select one transaction and edit it. Payments/expenses reuse existing admin editors (return to the ledger); transfers stay on `updateTransfer`; adjustments use `updateAdjustment`. Discount and `purchase_order` PAY rows stay locked.
- **2026-07-29 (partner party 26058):** Partner profit Party Master account is **26058** `Muhammad Faisal ( Partner )` (`IMPORT_PARTNER_PARTY_CODE`). Was `26014`. Remap open/paid accruals + `shipment_partner` PAYs: `database/migrations/2026_07_29_partner_profit_party_26058.sql`.
- **2026-07-29 (partner/packing/freight pay):** Bank account dropdown starts empty (`— Select bank account —`) so Pay cannot silently use the first ledger row (often CBK). Partner due also confirms account + amount before submit.
- **2026-07-29 (vendor bill AP):** Union packing follows invoice AP: `packing_dxb` estimate accruals excluded from party ledger; AP = `import_vendor_bills` matched to PAY. Packing due pay/link posts vendor bill; admin migrate replaces force-Clear. Migration: `database/migrations/2026_07_29_import_vendor_bills.sql`.
- **2026-07-29:** Packing due pays **Union’s invoice amount** (not ERP line total). ERP packing is an estimate until the bill arrives. Scope: all unpaid to date (default) or one month. Cash = invoice only. **Already transferred?** — `linkPackingExisting` links an existing Union PAY with no new cash.
- **2026-07-28:** Packing DXB (**Union Logistics**) settles **monthly** via Packing due (`packingDue` / `payPackingBulk`) — removed from immediate Freight payables. Freight payables can settle **one invoice total** per forwarder/shipment/leg (e.g. Hi-IQ 500 KWD covering two PO lines). Invoice amount defaults to whole KWD when allocated split is only rounding (≤0.5). If cash was already paid, use **Already paid? → Mark paid** (multi-PAY supported). Admin **Write off — no cash** only when no matching PAY remains. **Clear all Logix** links existing PAYs when possible, then **offsets residual net in `opening_balance`** so Party Master shows 0 (do not blindly set opening to 0 — that left PAY-only false credits). Party ledger counts import accruals `open` **and** `paid`.
- **2026-07-27:** Account Statement branch-scoped load failed with a generic DB error: payments JOIN parties left `warehouse_id` unqualified (ambiguous). Fixed to `p.warehouse_id` / `e.warehouse_id` (and `p.date` / `e.date`).
- **2026-08-13:** Posted PO advances live on the supplier ledger (receivable debit). Balance sheet **Prepaid Supplier Advances** is only leftover `paid_kwd` with no PAY row (avoids double-counting).
- **2026-07-07:** PO prepayments on open purchase orders count as **Prepaid Supplier Advances** in balance sheet `total_assets` (cash down + prepaid asset up — net worth unchanged until goods received).
- **2026-07-07:** Item master `purchase_price` auto-syncs from latest purchase line via `ItemCostService` (incl. full landed logistics + partner profit). Admin bulk sync on Items list.
- **2026-07-06:** Balance sheet category subtotals tie to `total_assets` (cash + receivables + inventory + PO prepayments); snapshots stored/read per `warehouse_id` — run `database/migrations/2026_07_06_networth_snapshots_warehouse.sql` on deployed DB.
- **2026-07-05:** Partner due detects duplicate same-day `shipment_partner` payments vs unlinked lump `purchase` pay; reconcile restores Partner due or links lines to existing lump without extra cash.
- **2026-06-11:** Audit SQL aligned with `AccountBalanceService` (payment cancel guards, PO unlinked, blank-ref duplicates, optional `@warehouse_id`).
- **2026-06-11:** Unified account balance formula — `computeFromLedgerAsOf()` powers Reconciliation, Account Statement, and Balance Sheet cash; branch reports use `(warehouse_id = ? OR warehouse_id IS NULL)`. Recalculate cleanup voids blank `ref_type` duplicate outbound payments.
