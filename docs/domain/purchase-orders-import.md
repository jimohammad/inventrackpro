# Purchase orders & import logistics

## Decisions

- **AED / USD PO** = overseas import — supplier price recorded in foreign currency. **Unit (KWD) and Total (KWD) stay blank** at create (unknown until the bank TT). Catalog purchase cost is not copied in. On Make Payment, type the exact bank KWD; when that settles the PO the app fills line KWD, header total, and `exchange_rate`. Freight/logistics still go through **Import Logistics**.
- **KWD PO** = **local purchase** — extra costs (delivery, freight) go through **Import Logistics**, not Other Charges on New PO. **All PO → purchase conversion goes through Import Logistics** (direct Convert on PO list/detail is disabled).
- Foreign price on PO is a **manual record/reminder** only — no automatic FX conversion (`exchange_rate` is typically 1 until a TT fully pays the PO).
- On **Make Payment**, AED/USD POs have a **TT rate** box: type the exact bank KWD; pay +/− adj fill so the PO total matches the bank. When that settles the PO, `exchange_rate` is stored as total KWD ÷ foreign, and KWD is written onto each line (by foreign weight).
- Payments on PO are recorded in **KWD only** (`paid_kwd`); `paid_foreign` is not used for settlement.
- **New PO** does not take payment, Other Charges, or Bank Adj. Save as unpaid `draft` with those amounts 0; pay later with **Payment Out** (open POs listed after supplier select, including AED/USD with blank KWD), or **Mark as Paid** (local KWD / already-priced POs only) / **Apply Supplier Credit** on the PO. Extra costs go through Import Logistics.

## Rules

### PO lifecycle

| Status | Meaning |
|--------|---------|
| `draft` | Created, not paid to supplier |
| `paid` | Supplier advance paid (KWD from account) |
| `converted` | Purchase invoice created, stock in |
| `cancelled` | Void |

### Currency

| Currency | Workflow |
|----------|----------|
| AED, USD | Link to Import Logistics shipment → receive → landed cost → convert |
| KWD | Link to Import Logistics (even with zero extra charges) → receive → convert |

### Pay PO from existing supplier credit

When Payment Out was already posted to the supplier (bank already reduced) and a PO is created later for only part of that advance:

1. On PO show use **Apply Supplier Credit** (not **Mark as Paid**).
2. Tick **one or more** unallocated `ref_type=purchase` / `ref_id=0` PAY rows and the total amount to cover this PO. Oldest selected PAY is applied first until the amount is filled.
3. System links/splits those payments to `ref_type=purchase_order` **without** deducting the bank again.
4. Leftover PAY amount stays on the supplier statement as credit for other items / a later PO.
5. Cancelling the PO returns all linked PAY rows to unallocated Payment Out. **Bank is not restored** — posted cash stays on the supplier ledger.
6. Linked `purchase_order` advances appear on the supplier statement as **PO Advance** (running balance includes them).

### Import Logistics

- POs with status `draft` or `paid` (any currency including KWD) appear in shipment form.
- One PO cannot be on two open (non-`applied`) shipments.
- Shipment `applied` = goods received in Kuwait; purchase invoices created via `LandedCostAllocator` / `PurchaseOrderConverter`.
- Shipment form only asks for **date** (plus POs and charges). Route stage, description, notes, and draft/in-transit status are not staff fields — new shipments are stored as `in_transit` / `dubai_hub` / description `Import shipment`. Status still becomes `applied` on Receive in Kuwait.

### Convert without shipment

- **Disabled** on PO list/detail (`PurchaseOrderController::convert` rejects requests).
- Staff must add the PO to an Import Logistics shipment and receive there.
- `PurchaseOrderConverter` remains in use only from Import Logistics receive.

## Code map

| Area | Location |
|------|----------|
| PO create/edit | `app/views/purchase_orders/create.php`, `edit.php` |
| PO detail + documents | `app/views/purchase_orders/show.php` |
| PO bank docs pack | `app/views/purchase_orders/print_docs.php` (`printDocs`) |
| Bank PO invoices & TT report | `ReportController::bankPoDocs` / `bankPoDocsPrint`, views `reports/bank_po_docs.php`, `reports/bank_po_docs_print.php` |
| PO bank pack verification QR | `PoDocsVerify`, `PoDocsVerifyController`, public `/d/{token}` |
| PO controller | `app/controllers/PurchaseOrderController.php` |
| Make Payment PO list / pay | `PaymentController::supplierOpenPos`, `Payment::createPurchaseOrderAdvances()`, `app/views/payments/create.php` |
| PO documents table | `purchase_order_documents` (`database/migrations/2026_07_16_purchase_order_documents.sql`) |
| PO picker | `LandedCostController::purchaseOrdersForForm()`, `assertPoLinkable()`, `poItems()` |
| Convert PO → purchase | `app/services/PurchaseOrderConverter.php` |
| Reverse purchase → PO | `PurchaseController::reverseToPo()`, `PurchaseOrderController::reverseToPo()`, `Purchase::reverseToPoWithReversals()` |
| Allocate landed cost | `app/services/LandedCostAllocator.php` |
| Import shipment UI | `app/views/purchases/landed_cost_form.php` |

### PO documents

- Attach **Supplier Invoice** and **TT Copy** on the PO show page (two dedicated upload fields).
- Allowed for **draft**, **paid** (ordered / awaiting goods), and **converted** POs — do **not** wait until conversion.
- Upload permission: `purchases.add` or `purchases.edit`.
- Cancelled POs cannot receive new uploads.
- Files live under `assets/uploads/po_docs/`; download goes through `downloadDoc` (auth + branch check).
- Scoped by `warehouse_id` on the document row and the parent PO.
- **Delete requires admin** — only `Auth::isAdmin()` can remove an uploaded file (UI + server check).
- List paperclip is active (indigo) when a PO has no docs yet so staff know they can attach.
- **One PDF for bank** (Documents card on PO show, when at least one file is uploaded): opens `printDocs`, merges cover + Supplier Invoices + TT Copies into `{po_no}_bank_docs.pdf`. Same permission as viewing a file (`purchases.view`). Files are fetched through `downloadDoc` (auth + branch). Password-protected or unreadable files are skipped and listed. Cover page is English/Latin only.
- **Bank report** (`?page=reports&action=bankPoDocs`, permission `rpt_bank_kyc`): lists Supplier Invoice and TT Copy files for a date range (default: uploaded date; optional PO date). Branch-scoped; cancelled POs excluded. **One PDF for bank** merges all files in that range (one cover with the PO list, then invoices and TT copies in PO order). Max 80 files per pack. `downloadDoc` also allows Official documents (`rpt_bank_kyc`) users so the pack can fetch files.
- **Verification QR** on both packs (single PO and date-range): signed token at `https://iqbal.app/d/{token}` (no login). Token binds warehouse + period or PO + invoice/TT counts + document-id fingerprint. Live check re-queries that **signed** `warehouse_id` only. Public page shows PO numbers and file counts — **not** supplier names. Secret is `po_docs_verify_secret` in `settings` (auto-created). PDF QR is a PNG from PHP GD (`QrSvg::pngDataUri`) embedded by pdf-lib.

### Dashboard (Awaiting Goods)

- Card amount = `SUM(paid_kwd)` on `draft`/`paid` POs for the session warehouse (cash already sent, usually non-refundable).
- Do **not** use PO grand total — unpaid drafts are a commitment, shown as a count under the card.
- Link opens PO list `status=paid` + `all_dates=1`.
- This amount is **not** dashboard Receivables (those are customer trade debts only).

### PO list filters

- First visit defaults to the **last two months** (same as Purchases) so the list opens quickly.
- Supplier and item filters are AJAX Select2 (type to search; no full catalog dump).
- New PO **Party name** field is the same: type at least one character (`searchParties`, `type=purchase`). No full party dump. Edit keeps the supplier locked.
- Supplier and item filters search **all dates** unless From/To are filled.
- Date range is optional and combines with supplier/item/status when set.
- List paints the latest **25** matching rows (Purchases list uses the same cap). Dates/search still find older documents.
## Verify

KWD POs on shipments should only be `draft` or `paid` (not `converted`):

```sql
SELECT spo.shipment_id, spo.po_id, po.po_no, po.status
FROM shipment_purchase_orders spo
JOIN purchase_orders po ON po.id = spo.po_id
WHERE po.currency = 'KWD' AND po.status NOT IN ('draft', 'paid');
```

Result should be empty.

PO documents must match the PO branch:

```sql
SELECT d.id, d.po_id, d.warehouse_id AS doc_wh, po.warehouse_id AS po_wh
FROM purchase_order_documents d
JOIN purchase_orders po ON po.id = d.po_id
WHERE d.warehouse_id <> po.warehouse_id;
```

Result should be empty.

## History

### 2026-09-06 — New PO has no Other Charges, bank adj, or on-form payment

- New Purchase Order form no longer has Other Charges. `other_charges_kwd` is always saved as 0. Extra costs belong on Import Logistics.
- New PO has no Bank Adj. field. `adjustment_kwd` is always saved as 0.
- New PO also has no Pay From Account / Amount Paid. Always `paid_kwd = 0`, `account_id` null, status `draft`. Pay later from **Payment Out** (select supplier → unpaid POs appear, with optional +/− bank adj) or **Mark as Paid** / **Apply Supplier Credit** on the PO.
- Totals sit in the same navy Grand Total card as New Sale (total qty, total foreign, KWD).

### 2026-09-06 — Pay open POs from Make Payment

- Selecting a supplier on Make Payment loads unpaid `draft`/`paid` POs for the session branch.
- Pay amount + optional +/− bank adj (`adjustment_kwd`) posts `ref_type=purchase_order` and updates `paid_kwd` (same ledger as Mark as Paid).
- AJAX: `PaymentController::supplierOpenPos`. Save: `Payment::createPurchaseOrderAdvances()`.

### 2026-09-06 — TT rate on Make Payment

- For AED/USD POs, Make Payment has **Foreign this TT** + **Bank took (KWD)**. Typing the exact bank amount fills pay, +/− `adjustment_kwd`, and shows `1 AED/USD = … KWD`.
- When that payment fully settles the PO, `exchange_rate` is stored as `total_kwd / subtotal_foreign` (display on PO show). Accounting is unchanged: cash out is still `paid_kwd`.

### 2026-09-06 — Blank KWD on AED/USD create

- New/edit PO no longer copies catalog `purchase_price` into Unit/Total KWD for AED/USD. Those fields stay empty until Make Payment TT.
- Open-PO list on Make Payment includes POs with zero KWD (previously hidden because unpaid was 0).
- Full TT pay writes KWD onto each line by foreign weight so convert/stock cost is the bank amount, not 0 + a large adj.
- Mark as Paid and import convert are blocked while KWD is still unknown.

### 2026-09-03 — Bank pack cover lists foreign amount

- Opening PO list shows supplier currency + amount (AED/USD `subtotal_foreign`) next to Paid KWD. Local KWD POs show "-".

### 2026-09-03 — Bank pack has one cover list, no per-PO pages

- Date-range **One PDF for bank** no longer inserts a cover page for every PO. The opening list is enough; original files follow.

### 2026-09-03 — Bank pack verification QR

- Bank PDF packs (single PO and date-range report) embed a signed QR. Scan opens `/d/{token}` (no login) and checks live invoice/TT files for the signed branch only. Supplier names are not shown.

### 2026-09-03 — Bank report of PO invoices & TT copies

- Reports hub **PO invoices & TT** (`rpt_bank_kyc`): date range of uploaded Supplier Invoice / TT Copy files (or by PO date). One merged PDF for the bank.

### 2026-09-03 — PO documents one PDF for bank

- PO show has **One PDF for bank** when Supplier Invoice / TT Copy files exist.
- Pack page merges a cover sheet, then invoices, then TT copies, in the browser (pdf-lib). No PHP PDF library.

### 2026-08-29 — PO / Purchases list show 25 rows

- Purchase Orders and Purchases lists paint the latest 25 matching rows (`INDEX_LIST_LIMIT`). Filters still find older documents. Cap banner removed 2026-08-29.

### 2026-08-28 — Dashboard Awaiting Goods is cash sent, not PO total

- Dashboard card no longer sums open PO grand totals (that mixed unpaid drafts with prepaid).
- Amount is `paid_kwd` on `draft`/`paid` POs; unpaid drafts are a count only.
- Same cash is stripped from dashboard Receivables (`Party::tradeReceivableTotals()`).

### 2026-08-23 — Shipment form drops unused header fields

- New/edit shipment no longer shows route stage, status, description, or notes (not used in day-to-day entry).
- New shipments still save `status=in_transit`, `route_stage=dubai_hub`. Edit updates date only so Receive/`applied` is not overwritten.

### 2026-08-22 — New PO create skips extra schema/lock queries

- Opening New PO no longer `FOR UPDATE` locks the last PO row just to preview `PO-00000N`.
- Item search no longer runs `SHOW COLUMNS` on every keystroke; AED/USD columns are selected directly (fallback query if missing).

### 2026-08-22 — New PO supplier field is type-to-search

- Create form no longer dumps all parties into the page. Supplier search uses `searchParties` (`type=purchase`).

### 2026-08-22 — PO list supplier/item filters are type-to-search

- No full party or item dump on page load. Same AJAX endpoints as Sales/Purchases (`searchParties` type=`purchase`, `searchItems` `stock=0`).

### 2026-08-15 — PO list opens like Purchases

- First visit defaults to last two months (was all dates / up to 500 rows).
- Select2 on supplier/item waits until after first paint (no 60ms poll).
- Supplier/item still searches all dates unless From/To are set.

### 2026-08-15 — PO list: item/supplier without a date range

- To date is no longer forced to today, so supplier/item search is all-time by default.
- From/To stay optional; filled dates still narrow the same supplier/item/status filters.
- List capped at 500 rows.

### 2026-08-13 — Posted PO cash stays on the supplier ledger

- Vendor down payment standard: once the bank TT is posted, the PAY is on Party Master / Supplier Statement (type **PO Advance**) and in Net Balance.
- Cancel PO no longer restores the bank or deletes the PAY — amount remains as unallocated supplier credit.
- Payments list: PO advances cannot be edited or deleted.
- Balance sheet prepaid line is only `paid_kwd` with no matching PAY (legacy unlinked).

### 2026-08-13 — Apply several supplier credits in one go

- Apply Supplier Credit now accepts multiple Payment Out rows in one submit (checkboxes). Oldest selected PAY is applied first until the PO unpaid amount is covered. Bank is still not deducted again.

### 2026-08-11 — Direct Convert PO → Purchase disabled

- Removed green Convert buttons from PO list and PO show.
- `PurchaseOrderController::convert` now only shows an error and points staff to Import Logistics.
- Conversion still happens when a shipment is received (`PurchaseOrderConverter` via Landed Cost).
- Goal: stop accidental convert-without-logistics mistakes.

### 2026-08-10 — PO document upload stuck on “Uploading…”

- Cause: global unsaved-changes guard treated the PO file form as dirty; `form.submit()` does not fire `submit`, so leave-protection could cancel the POST and leave the button stuck.
- Fix: ignore instant file-upload forms in the guard; PO upload uses `requestSubmit` + clear dirty; clearer PHP upload-limit errors.

### 2026-08-09 — Apply supplier credit to PO

- PO show: **Apply Supplier Credit** links an existing unallocated supplier Payment Out to the PO (split if needed). No second bank deduction.
- Cancel PO: all linked PAY rows return to unallocated `purchase` advance; **bank is not restored**.
- Edit PO: Amount Paid / account locked while credit-apply payments are linked.

### 2026-08-08 — PO create/edit item search fix

- `PurchaseOrderController::searchItems` no longer hard-depends on legacy `items.price_aed` / `price_usd` (detects columns; otherwise returns `0`).
- Search matches **name, name_ar, SKU, barcode, brand, model**; ranks exact SKU/barcode/name first.
- Soft JSON auth (no HTML 403); stock scoped to session warehouse.
- Create/edit autocomplete shows “No items found” / error instead of silently hiding the dropdown; ignores stale AJAX responses.

### 2026-07-21 — Progress card timestamps on PO show

- Progress card on PO detail now shows **date and time** for each completed step (PO Created, Payment Sent, Goods Received, Converted to Invoice).
- Sources: `purchase_orders.created_at`, earliest active `payments` row (`ref_type=purchase_order`), shipment `received_date` when applied, else linked purchase `created_at`.

### 2026-07-21 — Attach docs on ordered (pre-convert) POs

- Clarified that draft/paid POs can attach Supplier Invoice / TT Copy (same as converted); only cancelled blocks upload.
- Upload allowed with `purchases.add` **or** `purchases.edit` (ordering staff often have add without edit).
- List Docs column uses an active indigo paperclip when empty so it no longer looks disabled.
- Create redirects to the Documents section on PO show.

### 2026-07-16 — PO document attachments

- Added `purchase_order_documents` for **Supplier Invoice** and **TT Copy** on PO show (two dedicated upload fields).
- Upload/download/delete via `PurchaseOrderController`; MIME whitelist PDF/JPG/PNG/WEBP; max 10 MB.

### 2026-07-14 — PO paid vs total 3dp snap

- Entering a line **Total** that is not an exact multiple of qty at 3dp (e.g. `18510.550` ÷ 500 → unit `37.021`) left **Amount Paid** on the typed total while the UI later recomputed Total as `qty × unit` (`18510.500`).
- Balance Due used `max(0, total − paid)`, so a 0.050 overpay showed as **0.000**.
- Fix: snap Total = round(Unit × Qty, 3) after editing either field; clamp `paid_kwd` to grand when within `poPaidTolerance`; show **Overpaid** when paid > total.

### 2026-06-11 — Party balance vs PO prepaid

- `purchase_order` payments (PO paid, awaiting goods) are **excluded** from party Net Balance — they are prepaid, not AP.
- When a PO converts, party balance uses **PO KWD total** (`subtotal_kwd + other_charges_kwd + adjustment_kwd`), not purchase invoice `grand_total` if logistics inflated the invoice.

### 2026-07-22 — Bank adjustment on PO

- Added signed `adjustment_kwd` on purchase orders (create/edit Payment & Summary).
- Use **+** or **−** to make Total Amount match the exact bank amount deducted (fees / rounding).
- Formula: `total = subtotal_kwd + other_charges_kwd + adjustment_kwd`.
- Included in mark-paid, convert-to-purchase, party balance, reports, and dashboard PO totals.

### 2026-06-11 — KWD local PO + import exclusion

- Added KWD to PO currency dropdown for local suppliers.
- Excluded `currency = 'KWD'` from Import Logistics picker and `assertPoLinkable()`.
- Rationale: local purchases have no HK→DXB→Kuwait freight legs.

### 2026-07-06 — Reverse purchase to PO

- Added **Reverse to PO** on purchase detail and converted PO screens.
- Cancels the linked purchase (stock + payments reversed), reopens the source PO to `draft`/`paid`.
- Blocked when purchase is on an **applied** import shipment — undo shipment receive first.

### 2026-06-30 — Allow KWD POs on Import Logistics

- Removed KWD exclusion from `purchaseOrdersForForm()`, `poItems()`, and `assertPoLinkable()`.
- Local KWD POs can now be added to shipments when logistics charges apply.
