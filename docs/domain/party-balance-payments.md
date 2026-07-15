# Party balance & payments

## Decisions

- One **unified ledger per party** (customer and supplier are the same `parties` table).
- Balance is **branch-scoped** — never company-wide in UI.
- **Positive balance** = they owe us. **Negative** = we owe them (credit).

## Rules

### Balance formula

```
balance = opening_balance
        + sales (non-cancelled)
        - sale payments (receipts on sales + discounts)
        - sale returns (approved)
        - purchases (non-cancelled)
        + purchase payments (outbound to supplier)
        + purchase returns (approved)
        + open import payables (import_payable_accruals, status=open)
```

Sale-side payments use directional logic where applicable:

- `payment_type = 'in'` (receipt) → reduces what they owe  
- `payment_type = 'out'` on sale refs → increases what they owe  

Purchase-side / outbound payments in balance union: non-blank `ref_type`, excluding `purchase_order` (PO prepaid awaiting goods), `discount`, `expense`, and blank ref (duplicate advances). PO-converted purchase credits use the source PO KWD total, not logistics-inflated invoice `grand_total`.

### Payment direction (accounts)

- **Receipt** (`payment_type = 'in'`) → increases account balance  
- **Payment** (`payment_type = 'out'`) → decreases account balance  

### Customer credit limit

- `SaleValidator::enforceCreditLimit()` uses outstanding balance before allowing new sale.

### Sale returns (credit notes)

- An approved **sale return** is a **party ledger credit** (`returns.grand_total` for that `party_id` / branch).
- It does **not** reduce `sales.balance` on a linked invoice. Invoice AR stays `grand_total − paid_amount`.
- Optional `ref_id` (source invoice) is for IMEI / qty checks and audit only — not for allocating money against that invoice.
- Party statement already lists returns as credit lines; Party Master balance already subtracts sale returns.

### Legacy data

- `warehouse_id IS NULL` on old payments is treated as Main operational branch in balance clauses.
- Older invoices may still show a lower `balance` if returns were applied to the invoice before 2026-07-11. Opening/editing the sale recomputes invoice balance as `grand − paid` only; the party ledger remains the source of truth for what the customer owes.

## Code map

| Area | Location |
|------|----------|
| Balance SQL | `app/models/Party.php` — `batchBalanceUnionSql()`, `receivablePayableTotals()`, `partyBalanceRows()`, `currentNetBalance()` |
| Dashboard / Net worth / Balance sheet | `DashboardController`, `NetWorthService`, `ReportController::balanceSheet()` — all delegate to `Party` |
| Credit check | `app/services/SaleValidator.php` — `partyOutstanding()` → `Party::currentNetBalance()` |
| Payment create | `app/models/Payment.php`, `app/controllers/PaymentController.php` |
| Payment In list | `?page=payments` — `payment_type=in`, perm module `payments` |
| Payment Out list | `?page=payments&action=out` — `payment_type=out`, perm module `payments_out` (same store engine) |
| Statements | `app/controllers/ReportController.php`, `statement.php` |

## Verify

- Compare Party Master balance vs Account Statement for the same party/date range.
- Flag NULL-warehouse payments on the active branch (should be rare legacy rows only):

```sql
SELECT id, payment_no, party_id, date, amount, ref_type, warehouse_id
FROM payments
WHERE status = 'active' AND warehouse_id IS NULL
ORDER BY date DESC LIMIT 50;
```

## History

- **2026-07-14:** Split UI: **Payment In** (`payments`) vs **Payment Out** (`payments_out` menu + list). Same `payments` table / `store` engine; salesman/cashier without `payments_out` cannot open Make Payment or see OUT rows. Seed: `database/migrations/2026_07_14_payments_out_permission.sql`.
- **2026-07-14:** Payment In/Out UX: Fill due, remember last account, notes on Receive, shared thermal shortcut, Escape → Payments list; Payment Out balance uses payable perspective consistently (`partyBalance&mode=out` + labels).
- **2026-07-14:** Payment Out party search (`type=payment_out`) now allowed through `SalesController::searchParties` so suppliers **and freight forwarders** appear in the Make Payment field (was rejected and fell back to customers).
- **2026-07-11 (security):** Credit limit re-checked inside `Sale::createFull` (party `FOR UPDATE`); double-submit nonces on sale edit/add-item/IMEI-scan; cancel locks stock rows; CSRF failure redirect same-host only.
- **2026-07-11 (security):** Sale payments/reopen/FIFO reverse use invoice AR = grand − paid only (aligned with credit-note returns). Sale/return IMEI auto-create blocked; return prices capped to sold/catalog; returns forced to session warehouse; sales AJAX endpoints permission-gated; credit limit re-checked on edit/add-item.
- **2026-07-11:** Sale returns are party ledger credit notes only — no longer reduce `sales.balance` on the linked invoice (`Return::create` / void / edit; `Sale::recomputeBalanceAfterReturns` = grand − paid).
- **2026-07-11 (fix):** Sale credit check and create-draft balance now use `Party::currentNetBalance()` (branch-scoped ledger). `SalesController::store` forces `Auth::warehouseId()`; detail/print/pay/cancel/reopen/edit/add-item/scan assert session warehouse.
- **2026-07-02 (fix):** Public statement hardening — field statement (`FieldStatementController`) Balance card now uses `computeBalanceAsOf(today)` like `statement.php` (previously `net_balance` with no date cap, so future-dated entries made the two public pages disagree). `invoiceDetail` verifies tokens via `findByStatementToken()` and the sale-items query is warehouse-scoped like the sale query. Both public statement entry points now have IP rate limiting (60 hits / 5 min, shared temp-dir budget), `X-Frame-Options`/`nosniff` headers, escaped `ref_no`/modal output in `statement.php`, and SRI on the CDN stylesheet.
- **2026-06-11:** Party Statement opening balance now uses `Party::computeStatementOpeningBalance()` (same debit/credit rules as Party Master). Outbound supplier payments in `batchBalanceUnionSql()` use `payment_type = 'out'` (not a fixed `ref_type` list). Import payables on statements use `status = 'open'` only, matching balance SQL.
- **2026-06-11:** Inbound payments (`payment_type = 'in'`, excluding discount/expense) now count in batch balance (matches statement). Party Master **Suppliers** tab shows **payable** balance (positive = we owe them) via `Party::displayBalanceDue()`. Supplier Statement report uses the same unified ledger as Party Master.
- **2026-06-11 (fix):** Exclude **blank** `ref_type` outbound payments from balance (PO advance duplicates). Include other non-blank outbound rows (`sale`, `purchase`, shipment legs). **`purchase_order` payments are excluded** until PO converts (prepaid awaiting goods — not supplier AP). PO-converted purchases use **PO KWD total** (`subtotal_kwd + other_charges_kwd`), not invoice `grand_total` inflated by import logistics.
- **2026-06-28 (fix):** Customer discounts (`ref_type = 'discount'`) now appear on party statements and reduce batch balance via `sale_payments` (payment row carries `warehouse_id`; scoped like other branch transactions).
- **2026-06-11 (fix):** Purchase rows on party/supplier statements join `purchase_orders` for PO KWD credit; date filters must use **`pur.date`** (not bare `date`) to avoid MySQL “ambiguous column” errors that broke Customer/Supplier Statement reports.
