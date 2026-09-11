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
        - dump credits (approved `device_dumps`; folded into the sale_returns UNION arm)
        - purchases (non-cancelled)
        + purchase payments (outbound to supplier)
        + purchase returns (approved)
        + import payables (`import_payable_accruals` status `open` **or** `paid`, **except** `leg = packing_dxb`)
        + packing vendor bills (`import_vendor_bills` status `open` **or** `paid`)
```

Import freight/partner accruals count as purchase credits for both **open** and **paid** (not `cancelled`). Outbound settlement payments still count. That way paying Logix/Hi-IQ nets to **0** on Party Master — previously only `open` counted, so after pay the payment alone left a false credit/debit.

**Union Logistics packing** is different (invoice AP): shipment `packing_dxb` accruals are **costing estimates only** and are **excluded** from party balance/statement. Accounts payable is the vendor invoice in `import_vendor_bills`, matched 1:1 to the PAY amount. See `docs/domain/accounts-landed-cost.md`.

### Directional Party Balance Pattern

Same rule as the CLAUDE.md shorthand (“Party balance queries must use directional `CASE WHEN payment_type` logic”). Full detail lives here — do not invent a second formula.

Sale-side payments use directional logic where applicable:

- `payment_type = 'in'` (receipt) → reduces what they owe  
- `payment_type = 'out'` on sale refs → increases what they owe  

Purchase-side / outbound payments in balance union: non-blank `ref_type`, excluding `discount`, `expense`, and blank ref (duplicate advances). **Posted PO advances (`purchase_order`) count** — cash already left the bank (vendor down payment). PO-converted purchase credits use the source PO KWD total, not logistics-inflated invoice `grand_total`.

### Payment direction (accounts)

Cash/bank **account** balance (not party ledger) — see also CLAUDE.md “Payment direction: receipts increase balance, payments decrease”:

- **Receipt** (`payment_type = 'in'`) → increases account balance  
- **Payment** (`payment_type = 'out'`) → decreases account balance  

### Wholesale vs retail customers

- `parties.customer_kind` is `wholesale` (default) or `retail`. Suppliers / freight are stored as wholesale and ignored.
- Catalog `items.sale_price` is the **wholesale** list. Existing customers stay wholesale.
- **Retail** invoices auto-fill at **wholesale + 0.500** when the list is under 40 KWD, or **wholesale + 1.000** when the list is 40 KWD or more. Staff may raise the price; they cannot sell below that floor. Same floor applies to line/header discounts. No role or API override.
- Enforced in `SaleValidator::normalizeItems` / `assertRetailUnitPrice` on new sale, edit, add-item, and API create. Column is added on first use (`Party::ensureCustomerKindSchema`).

### Customer credit limit

- `SaleValidator::enforceCreditLimit()` uses outstanding balance before allowing a new sale, sale edit increase, or add-item. **No role bypass** — admin, manager, cashier, and API keys are all blocked.
- `credit_limit <= 0` means no cap. A positive limit is a hard cap on unpaid exposure (outstanding + this invoice).
- New Sale / Edit / Add Item disable Save when this invoice would exceed the remaining credit. Collect payment first — do not override on the invoice.
- Edit invoice: if Save is blocked by the limit, newly scanned IMEIs stay on the edit form (session draft) until the invoice actually saves. Collect payment (or raise `parties.credit_limit` on the party form, then return) and Save again — do not rescan.
- **Change customer on edit (admin):** the invoice picker can reassign `sales.party_id` to another active customer/`both` party on the same branch. Linked sale receipts (`payments.ref_type=sale`), invoice discounts (`customer_discounts.sale_id` + their `ref_type=discount` payments), and warranty replacements for that sale move with it. **Sale returns are not moved** (cross-party returns stay on the return party). Credit check on the new party uses the unpaid invoice remainder (`grand − paid`), not the full total. A salesman with an approved edit unlock **cannot** change the customer.

### Sale returns (credit notes)

- An approved **sale return** is a **party ledger credit** (`returns.grand_total` for that `party_id` / branch).
- It does **not** reduce `sales.balance` on a linked invoice. Invoice AR stays `grand_total − paid_amount`.
- Optional `ref_id` (source invoice) is for IMEI / qty checks and audit only — not for allocating money against that invoice.
- **Invoice detail** lists **Sale returns (credit notes)** only when the return **credits this invoice’s customer** and reversed **this sale** (not an older sale of the same IMEI). After a unit is returned to the warehouse it is **company stock**. A later sale to anyone else (and that customer’s return) is a new deal — it must not appear on the original invoice. `sale_item_imei` keeps history for IMEI timeline only.
- **Credit amount** = original **sold** `sale_items.unit_price` × qty (never current catalog `items.sale_price`). Resolved from scanned IMEIs via `sale_item_imei`, or from a unique price on the linked invoice; posted prices are ignored.
- **Cross-party IMEI returns are allowed:** a customer may return a unit originally sold to another party. Stock/IMEI restore from the original sale; ledger credit goes to the **selected return party** (not auto-forced to the original buyer). That credit note is **not** listed on the original buyer’s invoice. Still branch-scoped; already-returned / cancelled-invoice checks unchanged.
- Party statement already lists returns as credit lines; Party Master balance already subtracts sale returns.
- Approved **dump credits** (`device_dumps`) are the same direction as sale returns (reduce what they owe) and appear on the statement as **Dump**. They do not restock the phone. See [device-dumps.md](device-dumps.md).

### Legacy data

- `warehouse_id IS NULL` on old payments is treated as Main operational branch in balance clauses.
- Older invoices may still show a lower `balance` if returns were applied to the invoice before 2026-07-11. Opening/editing the sale recomputes invoice balance as `grand − paid` only; the party ledger remains the source of truth for what the customer owes.

### PO advances (posted cash)

- Once money leaves the bank (Mark as Paid or Payment Out), the PAY row is on the **supplier ledger** immediately — same as a vendor down payment in SAP/Oracle (posted cash is not hidden until GRN).
- `ref_type = purchase_order` is a label (advance vs invoice allocation), not a reason to omit the row.
- On convert, the same PAY flips to `ref_type = purchase` against the invoice (clearing). No second bank movement.
- Cancel PO does **not** restore the bank. PAY is unlinked to unallocated `purchase` credit (`ref_id = 0`).
- PO advances cannot be edited or deleted from Payments.

### Dashboard vs Party Master

- **Current Due / credit check / Party Master list** cap transactions at **today** (same as public statement and force-zero). Future-dated rows are excluded until that date.
- Party Master **timeline** uses the same debit/credit set as the statement (no expenses). Closing must match Due.
- Invoice Paid/Partial badges are FIFO on `sale` / `purchase` refs only — **`purchase_order` advances are not allocated onto invoices** until convert flips the PAY.
- **Party Master / balance sheet AR** still uses unified net (positive = they owe us), including supplier PO advances.
- **Dashboard Receivables** is **trade AR only**: `customer` + `both` parties, minus `paid_kwd` on open POs (`draft`/`paid`). Supplier/freight positive nets are excluded. That cash is prepaid goods, not collectible customer debt.
- **Dashboard Awaiting Goods** is `SUM(paid_kwd)` on those open POs (cash already sent). Unpaid drafts are a count only — not added to the amount.

### Payment Out / Purchase party types

- **Payment Out** party search includes `customer`, `supplier`, `both`, and `freight_forwarder` (buy-back / pay customer-only parties).
- **Make Payment** lists the supplier’s **open POs** (`draft`/`paid` with unpaid KWD) after a party is selected. Entering a pay amount posts `ref_type = purchase_order` (same as Mark as Paid) and updates `paid_kwd`. Optional **+ / − bank adj** is added to `adjustment_kwd` and included in the PO total. Client cannot POST `ref_type=purchase_order` as a freeform field — only validated `po_pay[]` lines. Leave PO amounts empty to post a normal unallocated Payment Out (FIFO onto purchase invoices).
- **Purchase** (and purchase return / PO) party pickers include `customer`, `supplier`, and `both` — customer-only parties can be purchased from without changing their type to supplier.
- Ledger rules are unchanged: purchases credit the party; payment `out` increases what they owe / reduces what we owe.

### New purchase invoice

- New Purchase does **not** take payment or discount. The invoice always saves with `discount = 0` and `paid_amount = 0` (status `confirmed` unless the total is 0).
- Supplier cash goes out later as a **Payment Out** voucher (`ref_type = purchase`). A crafted POST cannot create an on-invoice payment from this form.

### Supplier directory (who can see suppliers)

- **Salesman/cashier and viewer never see suppliers** — not in the sidebar, Party Master, global search, or party autocomplete (`type=supplier` is forced to customers).
- **Party Master does not grant supplier access.** Only the `suppliers` module does. Admin always has it; manager has it by default; cashier/viewer are hard-blocked even if a checkbox was left on.
- Freight Forwarders, Supplier Contacts, and Supplier Statement use the same rule (`suppliers` / `supplier_contacts` / `rpt_supplier_stmt`).
- Purchase / Payment Out pickers still search suppliers for users who have those modules (manager/admin).

## Code map

| Area | Location |
|------|----------|
| Balance SQL | `app/models/Party.php` — `batchBalanceUnionSql()`, `receivablePayableTotals()`, `tradeReceivableTotals()`, `partyBalanceRows()`, `currentNetBalance()` |
| Dashboard / Net worth / Balance sheet | `DashboardController` trade AR via `tradeReceivableTotals()`; Net worth / balance sheet still use `receivablePayableTotals()` |
| Credit check | `app/services/SaleValidator.php` — `partyOutstanding()` → `Party::currentNetBalance()` |
| Edit sale customer | `SalesController::update` / `reassignSalePartyLinks()` — moves invoice + sale receipts + invoice discounts; returns stay |
| Payment create | `app/models/Payment.php`, `app/controllers/PaymentController.php`; `Sale::addPayment` requires an existing **active** account; Make Payment PO advances via `Payment::createPurchaseOrderAdvances()` |
| Invoice AR repair | `Sale::refreshInvoicePaymentState()` on sale **detail** and payment writes — **not** on the sales list GET. Admin Party Master **Rebuild invoice badges** (`PartyController::rebuildAllocation` → `Payment::rebuildFifoAllocationForParty`) only updates invoice paid/balance/status; ledger and cash are unchanged. Highlighted only when `saleAllocationGap` is non-zero; otherwise collapsed under Admin. |
| Party autocomplete | `Party::search()`; AJAX `SalesController::searchParties` (`Auth::sanitizePartySearchType`). New Sale loads Due only after a customer is picked (`searchPartyBalances` for that one id). Search/list include `customer_kind`. |
| Retail price floor | `Party::unitPriceFloor()` / `SaleValidator` — retail is catalog + 0.500 (<40 KWD) or + 1.000 (40+); wholesale uses the list. |
| Party Master list | HTML from `getByType($type, false)` (no ledger UNION). Balances via `?page=parties&action=listBalances` (`getByType(true)`). Due/Clear tabs filter in the browser after balances arrive. |
| Supplier directory | `Auth::can('suppliers')` only (not Party Master). Cashier/viewer hard-blocked. `PartyController::canViewSuppliers()` / `authorizeParty()` |
| Payment In list | `?page=payments` — `payment_type=in`, perm module `payments`; newest **25** rows (search/dates/party still find older) |
| Payment Out list | `?page=payments&action=out` — `payment_type=out`, perm module `payments_out` (same store engine); same **25**-row cap |
| Statements | `app/controllers/ReportController.php`, `statement.php` |
| Bank KYC receipts PDF | `ReportController::bankKycReceipts` / `bankKycReceiptsPrint` — incoming only, branch-scoped |
| Bank PO invoices & TT | `ReportController::bankPoDocs` / `bankPoDocsPrint` — uploaded supplier invoices and TT copies, branch-scoped |
| Payment receipt print | `PaymentController::print` (A5 GCC voucher) / `thermalPrint` (80mm). Save & Print uses `Auth::printTemplate()` (profile Default Print). A5 is bilingual EN/AR (MOCI), amount in figures **and** words, CR/license from Settings when filled, Kuwait VAT-not-applicable line. QR encodes a signed public URL (`/r/{token}`) that verifies the live payment row. Computer-generated — no stamp or signature boxes. |

## Verify

Dashboard trade AR should be lower than (or equal to) unified AR when open PO advances exist:

```sql
-- Open PO cash (dashboard Awaiting Goods)
SELECT COALESCE(SUM(paid_kwd), 0) AS awaiting_goods
FROM purchase_orders
WHERE status IN ('draft', 'paid') AND warehouse_id = 1 AND paid_kwd > 0.001;
```

- Party Master detail: a Clear supplier with no sale-receipt gap must **not** show a prominent rebuild warning — only a collapsed Admin row. A customer whose receipts vs `sales.paid_amount` differ should show the warning with the amount and **Rebuild invoice badges**.
- Compare Party Master balance vs Account Statement for the same party/date range.
- Manual: log in as cashier/salesman — sidebar has Customers, not Suppliers / Freight / Supplier Contacts. `?page=parties&type=supplier` shows customers only. Opening a supplier `id` on party detail returns 403. New Sale party search does not return supplier-only names.
- Manual: edit a sale, scan extra IMEIs so the total exceeds the customer credit limit, Save → blocked (including as admin), scanned lines still on the form. Collect payment (or raise the limit on the party form, then return) and Save again without rescanning. New Sale Save is also disabled when this invoice would exceed remaining credit.
- Manual: New Customer → **Retail** → New Sale. Under 40 KWD list auto-fills +0.500; 40 KWD+ auto-fills +1.000. Salesman can raise it; Save at list price is blocked. Existing customers stay Wholesale and still use the list.
- Manual: Make Payment → pick a supplier with unpaid draft POs → list appears. Pay unpaid (+ optional adj) → PAY `purchase_order`, PO `paid_kwd` / status update. Empty PO amounts still save as a normal Payment Out.
- Manual: Sales list — same 25-row cap; search/customer/item/dates still find older invoices.
- Manual: Party Master names appear immediately (Balance Due shows …); amounts fill without a full reload. **With balance** / **Without balance** hide rows only after amounts load. Compare a few Due values to party ledger.
- Manual: Expenses list paints the latest **25** matching rows (`Expense::INDEX_LIST_LIMIT`). Search / category / dates still find older expenses (no yellow cap banner, no DataTables paging).

- Flag NULL-warehouse payments on the active branch (should be rare legacy rows only):

```sql
SELECT id, payment_no, party_id, date, amount, ref_type, warehouse_id
FROM payments
WHERE status = 'active' AND warehouse_id IS NULL
ORDER BY date DESC LIMIT 50;
```

- **2026-09-09 (UI):** Public customer statement (`/s/{token}`), field statement, and Reports → Party Statement DATE column show recorded time from `created_at` (`h:i A` under the date). Header/footer “as of / Generated on” already had a clock. Invoice modal uses the same label. No fake midnight if `created_at` is missing.

- **2026-09-09 (UI):** Payment A5 receipt redesigned as a GCC commercial voucher: EN/AR letterhead, amount in words, VAT-not-applicable (Kuwait). QR is a signed public verify URL (`PaymentReceiptVerify`, `/r/{token}`). Computer-generated — no stamp/signature boxes. Settings can store Arabic name/address, CR, and trade license for the letterhead.

- **2026-09-06:** New Purchase no longer accepts on-invoice payment or discount. Invoice posts unpaid; pay with Payment Out vouchers. POST `paid_amount` / `discount` / `account_id` are ignored.

- **2026-09-06:** Make Payment lists unpaid open POs after a supplier is selected. Allocating an amount (+ optional bank adj +/−) posts a `purchase_order` PAY and updates `paid_kwd` / `adjustment_kwd`. Empty PO amounts still save as a normal Payment Out.

- **2026-09-06:** New/edit customer has **Wholesale** vs **Retail**. Price list stays wholesale. Retail sales auto-fill catalog + 0.500 if under 40 KWD, or + 1.000 if 40 KWD+; staff may increase; save rejects below that floor. Existing parties default to wholesale.

- **2026-09-04:** Customer credit limit is a hard cap for every user including admin. New Sale / Edit / Add Item disable Save when this invoice would exceed remaining credit; `SaleValidator::enforceCreditLimit()` has no role bypass. Collect payment first (or raise the limit on the party form). `credit_limit` of 0 remains unlimited.
- **2026-09-04 (UI):** Customer view shows a last-3-months bar (sales `grand_total` and Payment In receipts, current branch only; discounts excluded).

- **2026-08-29:** Dump Credit (`device_dumps`) reduces party Due like a sale return (UNION arm folded into `sale_returns`). Statement type **Dump**. No payment `ref_type`. See [device-dumps.md](device-dumps.md).

- **2026-08-29 (ledger review):** Party Due / credit check cap at today so they match statement and force-zero. Party Master timeline no longer includes expenses (cash-only). Rebuild invoice badges skips `purchase_order` and `expense` PAY so PO advances are not painted onto purchase invoices.

- **2026-08-29 (perf):** Party Master list no longer waits on the 8-way ledger UNION before HTML. Names paint first; `listBalances` fills Due (same formula). UNION arms are pre-aggregated (6 scans, not 9 raw-row unions). Branch visibility uses uncorrelated `IN` subqueries instead of per-row `EXISTS`.

- **2026-08-29 (UI):** Expenses list uses the same 25-row cap as Sales / Payments (`Expense::INDEX_LIST_LIMIT`). Search and dates still find older expenses.

- **2026-09-03:** Reports hub **PO invoices & TT** (`bankPoDocs`) — supplier invoices and TT copies uploaded on POs, for a date range, one PDF for the bank. Same permission `rpt_bank_kyc`.

- **2026-09-02 (fix):** Payment In/Out **Save & Print** ignored Default Print and always opened the A5 voucher (`print_mode=1` never checked `Auth::printTemplate()`). It now follows A5 vs Thermal like sales/purchases. Dedicated `thermalPrint` action so Hostinger cannot drop the `thermal` query flag.

- **2026-08-29 (UI):** Removed the yellow “latest N records” cap banner from Sales, Payments, Purchases, Purchase Orders, Warranty, and statement/report lists. Row caps are unchanged.

- **2026-08-29 (UI):** Payment In/Out and **Sales** lists paint the latest **25** matching rows (`Payment::INDEX_LIST_LIMIT` / `Sale::INDEX_LIST_LIMIT`). Use dates/search/party to find older vouchers (no DataTables paging).

- **2026-09-10:** Salesman invoice edit is request → admin unlock (30 min or until invoice Save). Cashier still cannot change the customer on that unlock. See stock-imei.md.

- **2026-08-28 (UI):** Party Master detail no longer always shows “Admin: rebuild invoice allocation”. That tool never changed the ledger — it only replays payments onto invoice Paid/Partial badges. It is now a warning only when sale receipts and invoice `paid_amount` differ; otherwise it is collapsed. Supplier pages show purchase counts, not “Active sales: 0”.

- **2026-08-28:** Dashboard Receivables no longer includes supplier PO advances (trade AR via `Party::tradeReceivableTotals()`). Awaiting Goods card shows `paid_kwd` on open POs, not full order value.

- **2026-08-25 (security):** `Sale::addPayment` rejects missing or inactive `account_id` (POST only) so a receipt cannot credit a dummy till row.

- **2026-08-23:** Salesman/cashier could open Suppliers because Party Master (`parties` view) was OR’d onto the suppliers menu and party list. Decoupled: supplier directory requires `suppliers` (not `parties`). Cashier/viewer are hard-blocked on `suppliers`, `supplier_contacts`, and `rpt_supplier_stmt`. Party search `type=supplier` / `all` falls back to customers.

- **2026-08-23 (fix):** Sale invoice detail listed RET-00071 (Naqrashi, KWD 1,970) on Khalid Iqbal’s invoice because `sale_item_imei` still pointed at Khalid’s old sale after those phones were returned to stock and sold again. Rule: once an IMEI is back in the warehouse it is company property; a later sale/return is only that new customer’s business. Invoice credit-notes now require same party **and** this sale as the one being reversed (ignore later resales). Other-party returns are not shown on the original invoice.

- **2026-08-22:** New Sale customer search no longer computes Due for every match on each keystroke. Names/phones only (`balances=0`); ledger loads once when a customer is selected.

- **2026-08-22:** Payment In/Out list party filter is AJAX Select2 (type to search; no full party dump). Same `searchParties` endpoint as Sales (`balances=0`). Selected party is loaded only when the filter is already set.

- **2026-08-15:** Edit sale — credit-limit (or other) Save failure no longer drops newly scanned IMEIs; draft is restored on the edit page until the invoice saves.
- **2026-08-15:** Account ledger (bank/cash cards) links admin payment edit back to the selected account after save.
- **2026-08-13:** Posted PO advances (`ref_type=purchase_order`) are on the party ledger/statement/balance like other Payment Out. Cancel PO unlinks to supplier credit (bank not restored). PAY cannot be edited or deleted. Balance-sheet “Prepaid Supplier Advances” is only leftover `paid_kwd` with no PAY row.
- **2026-08-13:** PO **Apply Supplier Credit** can take several unallocated Payment Out rows in one action (FIFO until unpaid is covered). Same `ref_type=purchase_order` link, no second bank deduction.
- **2026-08-12 (fix):** Sale invoice edit Save showed generic 500 when the total increased — credit-limit check called `PackingVendorBillService::ensureSchema()` (`CREATE TABLE IF NOT EXISTS`) inside the open sale transaction; MySQL DDL implicitly committed, then `commit`/`rollback` threw. Schema ensure now skips mid-transaction; edit runs ensure before `beginTransaction`; update catch uses `Throwable` + `rollbackQuiet()`.
- **2026-08-11:** Sale returns may credit party A for an IMEI originally sold to party B (cross-party return). Ledger credit uses the return’s party_id; sold unit price still comes from the original sale line.
- **2026-08-09:** PO **Apply Supplier Credit** moves part/all of an unallocated supplier Payment Out onto `ref_type=purchase_order` (excluded from Net Balance until convert). Residual PAY stays on the supplier statement for other goods.
- **2026-08-09 (fix):** Party Master detail (`Party::getLedger`) no longer uses SQL `UNION ALL` for timeline rows — mixed table collations (`import_vendor_bills` vs older ledgers) raised MySQL 1271 “Illegal mix of collations”. Ledger now merges separate queries (same pattern as `getPartyStatementTransactions`). New `import_vendor_bills` tables are created with `utf8mb4_unicode_ci`.
- **2026-08-03 (hardening):** Payment/ledger loopholes closed — force-zero / Logix clear / statement repair are **admin-only** (no longer `payments:delete`); `PartyLedgerZeroService` no longer clears `parties.warehouse_id`; Receive/Make Payment validates party (active + branch + type) and whitelists `ref_type` (`discount`/`expense`/`purchase_order` rejected); `Payment::createStandalone` enforces the same whitelist; admin payment edit blocks discount rows, clears stale `ref_id` on party change, validates new party; sales AJAX `addPayment` requires `payments` add; `partyBalance` AJAX requires branch visibility; `Party::update` no longer writes `opening_balance`.
- **2026-08-03:** Party Master **Opening Balance** is locked on create/edit (readonly UI). Create always stores `0`; update ignores POST and keeps the existing DB value. System tools that adjust opening via dedicated SQL (e.g. Logix clear / ledger zero) are unchanged.
- **2026-07-30:** Named **Directional Party Balance Pattern** explicitly and cross-linked CLAUDE.md ↔ this file (graphify had flagged them as an undocumented “surprising” synonym pair).
- **2026-07-29:** Union packing AP uses `import_vendor_bills` (invoice ↔ PAY); `packing_dxb` accruals excluded from party balance/statement (estimates for costing / Packing due only).
- **2026-07-28 (fix):** Party Master / statement balance includes import accruals with status `open` **or** `paid` (not only `open`). After freight settle, liability + payment net to 0. Logix “Clear all” restores cancelled WRITE-OFF lines and links existing PAYs when cash already left the books; clears party balance list cache.
- **2026-08-22 (perf):** Receive/Make Payment no longer runs the 8-way balance UNION for every customer keystroke. Names/phones only while typing; Due + Fill due load once after the party is selected. Linked/preselected party balance is computed on the form load (no second AJAX). Account dropdown is one query on a healthy ledger.
- **2026-08-20 (perf):** Sales list Customer/Item filters are AJAX Select2 (no full catalog dump). List SQL drops unused warehouses join. New Sale party search is two-phase (`balances=0` then `searchPartyBalances`).
- **2026-07-25 (fix):** Receive/Make Payment linked to a sale/purchase (`ref_id`) now allocates to that invoice first; remaining amount still follows FIFO. Rebuild invoice allocation uses the same rule. Sale **detail** auto-repair replays allocation when linked or same-day standalone receipts should cover the invoice, then syncs Paid/Partial badges from `grand_total − paid_amount`.
- **2026-07-21 (hardening):** Payment FIFO create/delete scoped to `warehouse_id`; import payment delete reopens **all** accrual legs (not partner-only); branch assert on detail/print/edit/delete; `partyBalance` AJAX permission-gated; admin-only Edit UI aligned with controller; success flash before print; dead list `getSummary` call and unused create CSS removed.
- **2026-07-21:** Receive/Make Payment: **Save, Print & New** returns to a fresh form with the same party after the voucher prints (cash then bank as two separate receipts). Split-payment UI removed — one voucher per save only.
- **2026-07-21 (fix):** Party Master field-statement link (🔗) was missing because `getByType()` did not select `statement_token`, and legacy parties had NULL tokens. List now selects/backfills tokens; Agent Statement ensures a token before showing **Copy Field Link**.
- **2026-07-20:** Purchase and Payment Out allow **customer-only** parties (no need to mark as supplier/both). Search types: `purchase` (customer/supplier/both), `payment_out` (+ customer). Payment Out Due always uses payable perspective (`-net`) so Fill Due works after buying from a customer.
- **2026-07-19:** Sale returns always credit at the customer’s original sold unit price (server overwrites client price; no catalog fallback).
- **2026-07-15:** Added **Bank KYC Receipts** report (`?page=reports&action=bankKycReceipts`) — A4 formal PDF of incoming payments (`payment_type=in`) for a period; discounts excluded by default; optional bank-like method filter. Same permission `rpt_bank_kyc`.
- **2026-07-15 (perf):** Party autocomplete (`Party::search`) no longer runs correlated `EXISTS` visibility subqueries on every keystroke — uses home-branch / unassigned filter only. Prefix matches rank first. Debounce 150→300ms.
- **2026-07-15:** Supplier trade license on Party Master — `parties.trade_license_file` + `trade_license_expires_on` (supplier / both / freight). Upload PDF/image under `assets/uploads/parties/`; dashboard shows expired + due-in-7-days chips. Migration: `database/migrations/2026_07_15_parties_trade_license.sql` (also auto-`ALTER` via `Party::ensureTradeLicenseSchema()`).
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
- **2026-06-11 (fix):** Exclude **blank** `ref_type` outbound payments from balance (PO advance duplicates). Include other non-blank outbound rows (`sale`, `purchase`, shipment legs). **`purchase_order` payments are excluded** until PO converts (prepaid awaiting goods — not supplier AP). PO-converted purchases use **PO KWD total** (`subtotal_kwd + other_charges_kwd + adjustment_kwd`), not invoice `grand_total` inflated by import logistics.
- **2026-06-28 (fix):** Customer discounts (`ref_type = 'discount'`) now appear on party statements and reduce batch balance via `sale_payments` (payment row carries `warehouse_id`; scoped like other branch transactions).
- **2026-06-11 (fix):** Purchase rows on party/supplier statements join `purchase_orders` for PO KWD credit; date filters must use **`pur.date`** (not bare `date`) to avoid MySQL “ambiguous column” errors that broke Customer/Supplier Statement reports.
