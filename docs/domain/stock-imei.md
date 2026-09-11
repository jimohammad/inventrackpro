# Stock & IMEI

## Decisions

- Stock is **per warehouse** (`stock.item_id` + `stock.warehouse_id`).
- Stock changes must be **atomic** with the business document (sale, purchase, return, transfer).
- IMEI-tracked items require serial linkage on sale/purchase/return flows.

## Rules

### Stock movement

| Document | Stock effect |
|----------|----------------|
| Purchase (approved) | Increase qty in warehouse |
| Sale (approved) | Decrease qty (lock row `FOR UPDATE`) |
| Sale return (approved) | Increase qty (reverse original sale movement) |
| Purchase return (approved) | Decrease qty |
| Stock transfer | Decrease source, increase destination |
| Shop-to-shop transfer (Main ↔ Lite) | Decrease sender shop, increase receiver shop (separate databases; SKU + IMEI; no invoice) |
| Warranty replacement (create) | Decrease qty for replacement device given out |
| Warranty replacement (edit) | If replacement item changes: +1 previous item, −1 new item; IMEI statuses reversed/reapplied |

### Transactions

- Critical paths use `beginTransaction()` + `SELECT ... FOR UPDATE` on `stock` rows.
- Never update stock outside a transaction for invoice-level operations.
- Returns must reverse the **original** stock movement and respect the document’s `warehouse_id`.

### Warehouse

- All stock queries in operational UI must scope to `Auth::warehouseId()` / `WarehouseScope`.
- See [warehouse-isolation.md](warehouse-isolation.md).

### IMEI

- `imei_records` tracks unit state; link tables connect to sale/purchase/return line items.
- **Strict sale rule:** if `items.has_imei = 1`, the sale **cannot** complete unless scanned IMEI count equals line quantity. There is **no** “serial optional at sale” bypass (`imei_optional` is forced to `0` and ignored on sale create / edit / add-item). Accessories with `has_imei = 0` still sell without serials.
- **SKU (`items.sku`):** optional catalog code on Item Master (Identity). Saved uppercase. The same SKU on Main and Lite is required to import a sales invoice as a purchase. See [invoice-file-exchange.md](invoice-file-exchange.md).
- **Serial kind (`items.serial_kind`):** `phone` (default) = digit IMEI (H40 = 13, others 15–18). `tablet` = alphanumeric carton serial **11–20** chars with at least one letter (e.g. Samsung `R8YL60BBJBD`). Carton **case numbers** (shorter codes like `L69K17435`) are not unit serials. Set on Item Master under Serial tracking. Name/category containing “tablet” is treated as tablet if the field is still `phone`. Hostinger: column is added on first use (`Item::ensureSerialKindColumn`).
- **Strict warranty replacement:** create and edit **cannot** save unless both **old IMEI** (faulty device) and **new IMEI** (replacement device) are filled. Enforced in `WarrantyController::store` / `update` and the create/edit Save buttons.
- Enforced in `SaleValidator::normalizeItems`, `SalesController::update` / `addItemStore`, and client submit guards on create / edit / add-item. Edit save also rejects invoices that still have incomplete IMEI lines (use Scan IMEIs / admin `scanItemImeis` to fix historical gaps).
- **Returned IMEI is company stock:** when any party returns serials, they re-enter the warehouse as company property and may be sold to any customer. That later sale (and any later return) has **no** ledger or invoice link to the previous buyer. IMEI History still shows the full chain via `sale_item_imei`; invoice **Sale returns** only lists credit notes that reversed **that** sale for **that** customer.
- **Dump credit (unrepairable):** a sold IMEI that cannot be repaired is **not** a sale return. `Dump Credit` marks the serial `dumped`, credits the party (in-app: original sold price; pre-April 2026 / not in app: staff-entered amount), and does **not** increase phone stock. See [device-dumps.md](device-dumps.md).
- **Cross-party sale return:** a sold IMEI may be returned on a credit note for a **different** party than the original buyer. Stock/IMEI restore from the original sale (sold price + remaining qty on that invoice); ledger credit uses the return’s `party_id`. The original invoice does not list that credit note. Still blocked for wrong branch, cancelled invoices, and already-returned serials.
- **Buy-back / re-purchase scan:** if a unit was previously **sold** (or transferred) and is bought again on a purchase invoice, purchase IMEI scan **re-stocks** the existing `imei_records` row (`status → in_stock`, link `purchase_id`, clear `sale_id`). Does **not** insert a duplicate. Still blocked if the IMEI is already `in_stock`/`returned`. Undo detach restores `sold` when sale history exists (does not hard-delete). Enforced in `IMEI::attachToPurchase` / `detachFromPurchase` via purchase scan station.
- **Salesman max qty:** `items.max_sale_qty` (0 = unlimited). Cashier/viewer roles cannot sell more than this quantity of the item **on one invoice** (duplicate lines are summed). Admin/manager unrestricted. Enforced in `SaleValidator::normalizeItems` (create) + sales create UI; set on Item Master next to Minimum stock. Migration: `database/migrations/2026_07_30_items_max_sale_qty.sql`.
- **New sale item autocomplete:** one AJAX call. `Item::search` finds 15 names first, then attaches branch stock for those ids only (`attachSearchStock`). Arrow keys / Enter pick a line. Other pages use the same search. `purchase_price` is returned only when the user can view purchases (not cashiers/salesmen).
- **Salesman price floor (new sale):** Cashier/viewer cannot sell **below** catalog `items.sale_price` on unit price **or** via invoice/line discount (UI + `SaleValidator` clamp). API create uses reject mode unless the key has `sales_price_override`. Temporary free-price override: `SalesController::TEMP_ALLOW_SALESMAN_FREE_PRICE` (currently `false`). Max-qty rules stay enforced.
- **Salesman invoice edit (admin unlock):** Cashier cannot edit a saved invoice until admin approves a request. Unlock lasts **30 minutes** or until the salesman **Saves the invoice** (`SalesController::update`), whichever first. Add Item and Scan IMEIs work during the window and do not close it. Stock/IMEI/ledger change only on Save, not on approve. Cashier still cannot change customer, cannot sell below catalog/retail floor, and max-qty still applies. Admin can edit anytime without a request. Cancelled invoices cannot be requested. One pending request per invoice. Table `sale_edit_requests` (created on first use).
- **Retail customer price floor:** Catalog `items.sale_price` is wholesale. For `parties.customer_kind = retail`, New Sale / Edit / Add Item auto-fill **wholesale + 0.500** if the list is under 40 KWD, or **+ 1.000** if 40 KWD or more. Staff may increase; every sale path (create / edit / add-item / API) **rejects** a unit price or discount below that floor. `sales_price_override` and admin cannot sell a retail customer at the wholesale list. Wholesale customers keep the existing salesman clamp / admin free-price rules.
- **Stock List quantity does not depend on IMEI scans.** PO convert / purchase receive updates `stock.quantity` immediately; scanning IMEIs only fills serial registry for sell/warranty.
- **Customer pricelist PDF (one A4):** Stock List → **Pricelist PDF**. Same look as public `/pricelist` (category pills, item cards, NFC, In Stock / Low Stock, KWD price). Active items with `quantity > 0` on the session branch. No qty numbers, IMEI, or cost. Built for ~25 SKUs on one page. `StockController::pricelistPrint`, view `inventory/pricelist_print.php`.
- **New purchase:** serials are not required at invoice create. Save the purchase, then scan from the invoice **Scan IMEIs** page. The create form no longer has a “Scan IMEIs later” checkbox.
- Stock List IMEI column compares `stock.quantity` vs count of `imei_records` with status `in_stock`/`returned`:
  - **pending** = Qty > IMEIs (units on hand not yet scanned). Stock List links pending to IMEI Audit.
  - **over** = IMEIs > Qty (serials still marked available after stock was reduced — investigate via IMEI History; often caused by past sales without serial scan, or a warranty replacement that deducted qty without a replacement IMEI)
  - Stock List mismatch rows run `StockQuantityService::imeiOverDiagnosis` and link **over** / **pending** to IMEI Audit (`?page=imei&action=audit&item_id=…`).
- **No free-form IMEI Register:** serials must enter via **purchase scan**, sale/purchase return, or admin Stock Audit register-missing. Inventory → **IMEI Register** (scan onto existing stock + Clear all) is removed.

- **Physically missing pending units (admin):** IMEI Audit shows **Write off N missing units** when book qty is higher than scanned IMEIs. That inserts `stock_adjustments.quantity_delta` (negative) and rebuilds `stock.quantity` to the IMEI count. Rebuild stock then keeps the write-off. Use a **purchase return** instead if the supplier still owes the phones. Do **not** Register Missing IMEIs for units that are not on the shelf.
- **Physical count write-off (admin, any SKU):** Stock List — click the qty (e.g. buds 124, counted 110) → enter shelf count → write off the difference. Same `stock_adjustments` table. IMEI-tracked items cannot go below scanned serials (use IMEI Audit first if extra IMEIs are still marked available). Accessories (`has_imei = 0`) can be written off to any count from 0 up to book qty. **TEMP off:** `StockQuantityService::TEMP_ALLOW_STOCK_WRITEOFF = false` hides the UI and blocks POST even for admin. Set `true` to restore.
- Admin **Rebuild stock** on purchase detail recomputes `stock.quantity` from opening + purchases − sales ± returns ± transfers ± `stock_adjustments` (`StockQuantityService`).
- Reports: `app/views/reports/customer_imei.php`, `app/controllers/IMEIController.php`.
- Customer Return IMEI (party sale returns + serials): `ReportController::returnImei` / `returnImeiPrint` / `returnImeiExport`, views `reports/return_imei.php`, `reports/return_imei_print.php` (permission `rpt_return_imei`). Branch-scoped; cancelled returns excluded; shows original sale invoice when linkable.
- Bank KYC (period sales + IMEI A4 PDF): `ReportController::bankKyc` / `bankKycPrint`, views `reports/bank_kyc.php`, `reports/bank_kyc_print.php` (permission `rpt_bank_kyc`).
- Bank PO invoices & TT (uploaded supplier invoices and transfer copies): `ReportController::bankPoDocs` / `bankPoDocsPrint`, views `reports/bank_po_docs.php`, `reports/bank_po_docs_print.php` (same permission `rpt_bank_kyc`).
- Manpower formal invoices (last 3 months A4 PDF copies): `ReportController::manpowerInvoices` / `manpowerInvoicesPrint`, views `reports/manpower_invoices.php`, `reports/manpower_invoices_print.php` (same permission `rpt_bank_kyc`). Branch-scoped; cancelled excluded.
- Manpower verification QR: signed public page `VerifyController` at `/v/{token}` (no login). Token binds session warehouse + period + printed totals; live check re-queries that same `warehouse_id` only. No customer names on the public page. Print/PDF embeds the QR as SVG in PHP (`QrSvg`) — it does not load qrcode.js from a CDN.

## Code map

| Area | Location |
|------|----------|
| Sale stock | `app/models/Sale.php` |
| New sale item search | `Item::search` / `Item::attachSearchStock`, `SalesController::searchItems` / `searchItemStocks` |
| Purchase stock | `app/models/Purchase.php`, `PurchaseController.php` |
| PO convert stock | `app/services/PurchaseOrderConverter.php` |
| Stock qty rebuild / missing-unit write-off | `app/services/StockQuantityService.php` (`stock_adjustments`) |
| Customer pricelist PDF (1× A4) | `StockController::pricelistPrint`, `app/views/inventory/pricelist_print.php` |
| Returns | `app/models/Return.php` |
| IMEI | `app/models/IMEI.php`, `IMEIController.php`, `app/helpers/ImeiFormat.php` |
| Shop-to-shop stock | `IntershopTransfer`, `IntershopController`, `api/v1/intershop.php` |
| Sales invoice JSON → purchase | `SaleInvoiceFile`, `SalesController::exportInvoice`, `PurchaseController::importInvoice` |
| Sale validation | `app/services/SaleValidator.php` (`normalizeItems`, `normalizeHeaderDiscount`) |
| Salesman edit unlock | `SaleEditRequest`, `SaleEditRequestController`, `SalesController::requireSaleEditAccess` |
| Warranty replacements | `WarrantyController.php` (create/edit reverses IMEI + stock when item/IMEI change; branch locked on edit) |
| Dump credit | `DumpController.php`, `DeviceDump.php` — sold IMEI → `dumped`; party ledger credit; no phone restock |
| Public warranty page | `app/views/public/imei_track.php` (`/imei`, `?page=imeitrack`) |
| Manpower invoices PDF / QR | `ReportController::manpowerInvoicesPrint`, `app/helpers/QrSvg.php`, `app/helpers/ManpowerVerify.php` |
| Customer warranty PWA | `assets/pwa/imei/` (manifest, sw.js, icons); scope `/imei` only — do **not** put PWA files under `/imei/` (pretty-URL rewrite turns them into HTML) |

## Verify

- Manual: cashier opens a saved invoice → Request edit → admin **Sale edit requests** Approve → cashier sees Edit for 30 min → Save closes unlock; a second Save without a new request is blocked.
- Manual: Stock List → **Pricelist PDF** → one-page A4 PDF downloads; items match in-stock active SKUs on this branch; PDF has names + sale prices only (no qty/IMEI).
- Manual: New sale item search — Stock shows a number; arrows/Enter pick a line.
- Manual: convert PO / receive purchase → item qty on Stock List before any IMEI scan.
- Manual: try to save a new sale for an IMEI-tracked item **without** scanning → blocked (client + server). Same for edit add-line and add-item.
- Manual: New Replacement / Edit without Old IMEI or New IMEI → Save stays disabled; POST is rejected with “Old IMEI and New IMEI are both required.”
- Manual: open an old invoice with incomplete IMEIs → edit Save blocked until Scan IMEIs fills the gap.
- Manual: set item **Salesman max qty** = 2 → cashier create sale with qty 3 (or two lines totaling 3) → blocked; admin can still sell 3+.
- Manual: cashier New Sale — posting `discount` or `items[][discount]` equal to the line total must not save below catalog (clamped to 0 extra off catalog; invoice total stays ≥ catalog × qty).
- Manual: mark a customer **Retail** → New Sale auto-fills list + 0.500 if under 40 KWD, or + 1.000 if 40 KWD+; salesman can raise it; Save at wholesale list is blocked. Wholesale customers still use the list.
- Public PWA: open `https://iqbal.app/imei` on phone → Install / Add to Home Screen; lookups still hit the network (not stale cache).
- Manual: Stock List pending (qty > IMEIs, units physically missing) → IMEI Audit → **Write off N missing units** → qty equals IMEI count; Rebuild stock on a purchase of that item does not restore the written-off qty.
- Manual: Stock List admin click qty (buds 124, counted 110) → physical count 110 → qty 110; rebuild does not restore the 14.
- SQL: no negative qty (add audit if needed):

```sql
SELECT item_id, warehouse_id, quantity FROM stock WHERE quantity < 0;
```

```sql
SELECT item_id, warehouse_id, SUM(quantity_delta) AS adj
FROM stock_adjustments
GROUP BY item_id, warehouse_id;
```

- SQL: tablet SKUs (after first item save / search, column exists):

```sql
SELECT id, name, serial_kind, has_imei FROM items WHERE serial_kind = 'tablet' OR LOWER(name) LIKE '%tablet%';
```

- SQL: items with a salesman cap:

```sql
SELECT id, name, max_sale_qty FROM items WHERE max_sale_qty > 0 ORDER BY name;
```

- SQL: clear leftover optional flags (after deploy migration):

```sql
SELECT COUNT(*) FROM items WHERE imei_optional = 1; -- expect 0 after migration
```

## History

- **2026-09-08:** Stock List **Pricelist PDF** — one A4 page matching public `/pricelist` (cards, NFC, In/Low stock badges). Wholesale `sale_price` for in-stock active items (session warehouse). PDF uses `html2pdf()`. For sharing with customers; not an invoice.

- **2026-09-07:** Item Master form and items list show **SKU** (saved uppercase). Needed so invoice import can match catalog lines.

- **2026-09-07:** Sales invoice JSON export / purchase import between shops. See [invoice-file-exchange.md](invoice-file-exchange.md). `Purchase::createFullPurchase` now attaches posted serials via `IMEI::attachToPurchase` (fails the invoice if a serial cannot be added). Empty IMEIs on create still mean scan later.

- **2026-09-07:** Stock-only shop transfer to Lite (`intershop`). See [intershop-stock.md](intershop-stock.md).

- **2026-09-06:** New purchase page: removed the “Scan IMEIs later” checkbox. Serials stay optional at create; scan from the invoice when ready.

- **2026-08-29:** Dump Credit for unrepairable sold units (`dumped` IMEI, party credit, no restock). Pre-April 2026 serials not in the app can be dumped with a typed amount. See [device-dumps.md](device-dumps.md).

- **2026-09-03:** Removed Inventory → **IMEI Register** (free-form scan onto existing stock, plus admin Clear). Serials are added on the purchase IMEI scan station. Old URLs `?page=imei&action=register` / `saveImei` / `clearItemImeis` are disabled.

- **2026-09-03 (fix):** Purchase IMEI paste **Validate** was format-only (`1 valid`) and did not save. Confirm Import could skip a serial (already in stock / already on this purchase / dumped) then close the modal without showing why, so the count stayed at 349. Validate now dry-runs attach on the server; skipped reasons stay in the modal; Enter validates then imports.

- **2026-08-25:** Tablet carton serials (alphanumeric, e.g. `R8YL60BBJBD`) are a first-class serial kind. Item Master: IMEI tracking + **Tablet serial**. Purchase/sale/return scan and paste accept 11–20 letter/number codes; carton EAN barcodes (digits only) and short case numbers are rejected for tablet SKUs.

- **2026-08-25 (security):** Item autocomplete omits `purchase_price` unless the user has purchases view. GET `checkImei` validates stale-sold serials without writing `imei_records` (`validateList(..., healStaleSold: false)`); heal still runs on sale save.

- **2026-08-25 (security):** Salesman price floor now includes invoice and line discounts (`SaleValidator::normalizeHeaderDiscount` + line-discount cap vs catalog). POST `discount` / `items[][discount]` can no longer zero a cashier invoice. API create uses the same floor unless `sales_price_override`. API sale POST requires a warehouse-scoped key (same as GET) and creates `api_idempotency` **before** the sale transaction (MySQL DDL implicit commit).

- **2026-08-24:** Manpower verification QR is a dotted SVG (circular data modules, square finders) printed at 38mm on the cover and 24mm on invoice copies so phones can scan it from the PDF.

- **2026-09-10:** Cashier can request to edit a saved sales invoice; admin approves a 30-minute unlock (or until invoice Save). Customer change stays admin-only. Price floor and max qty stay on.

- **2026-08-24:** Manpower certification uses **Account department** (no computer-generated signature, no personal name). Blank line is for Accounts to sign; company stamp is unchanged. Bank KYC packs still use Authorized signatory + signature image.

- **2026-08-24:** Manpower pack PDF QR is generated in PHP (`app/helpers/QrSvg.php`) and embedded as SVG. Previously the print page waited on cdnjs `qrcode.min.js` plus `window.load` before opening the print dialog, which made Save as PDF feel stuck on the QR.

- **2026-08-24:** Added **Manpower Invoices** report (`?page=reports&action=manpowerInvoices`) — A4 certified copies of sales invoices for Public Authority of Manpower. Pack PDF includes a signed verification QR (`/v/{token}`); scan checks live branch sales for the printed period. Customer names are not shown on the public page.

- **2026-08-23 (fix):** Original-sale invoice was attaching later returns of the same IMEI (RET-00071 Naqrashi on Khalid’s invoice) because `sale_item_imei` is historical. After restock, units are company stock; a later customer’s return must not appear on the previous invoice. `getLinkedToSale` requires same party and no later resale before the return.

- **2026-08-22:** New Sale Return customer search matches New Sale: `searchParties` `type=customer` `balances=0` (names, LIMIT 15), then `searchPartyBalances` for those ids only. No full party dump; Due is not computed on every keystroke.

- **2026-08-22:** Returns list customer filter is AJAX Select2 (type a name; no full party dump). Uses `searchParties` `type=customer` `balances=0`.

- **2026-08-22:** Purchases list item filter is AJAX Select2 (type name/SKU; no full catalog dump). Uses `SalesController::searchItems` with `stock=0`.

- **2026-08-20:** New sale item autocomplete uses one AJAX call. `Item::search` finds 15 names then attaches stock for those ids (no catalog-wide stock JOIN). Arrows/Enter select. Two-phase browser stock fetch was dropped because stock stayed blank on iqbal.app.
- **2026-08-18:** Warranty create/edit always require both old (faulty) IMEI and new (replacement) IMEI — no blank serials.
- **2026-08-18:** Qty write-off temporarily disabled for everyone including admin (`StockQuantityService::TEMP_ALLOW_STOCK_WRITEOFF = false`).
- **2026-08-18:** Admin can write off any SKU from Stock List (click qty → physical count), including accessories such as buds. IMEI pending write-off stays on IMEI Audit and is limited to `has_imei` items.
- **2026-09-06:** Retail customers cannot be sold at catalog `sale_price`. New Sale / Edit / Add Item auto-fill wholesale + 0.500 if under 40 KWD, or + 1.000 if 40 KWD+ (staff may increase). API / save reject below that floor (`Party::customer_kind`, `SaleValidator`). Existing customers remain wholesale.
- **2026-08-18:** IMEI pending (qty > serials) on Stock List links to IMEI Audit. Admin can **write off** physically missing units (`stock_adjustments`); rebuild includes those deltas so phantom qty cannot return. Inactive items with qty still appear on the audit mismatch list.
- **2026-08-18:** Stock List **IMEI over** now diagnoses likely cause (warranty without replacement IMEI, unscanned sale, stale available serial, duplicate IMEI). Warranty create/edit require both old and new IMEI.
- **2026-08-15:** Warranty list first visit defaults to last two months (500-row cap). Search without a date range still looks at all dates. Unused customer/item dropdown queries removed from the list page.
- **2026-08-12:** Purchase IMEI scan supports **buy-back**: previously sold/transferred IMEIs can be scanned onto a new purchase (`IMEI::attachToPurchase`) instead of “exists in another record”. Undo preserves sale history via `detachFromPurchase`.
- **2026-08-12:** Added **Customer Return IMEI** report (`?page=reports&action=returnImei`) — party sale returns with IMEI/serials, original invoice, Excel/PDF print. Permission `rpt_return_imei`.
- **2026-08-13:** Disabled TEMP free price for cashier/viewer on new sale (`TEMP_ALLOW_SALESMAN_FREE_PRICE = false`). Min-price floor restored.
- **2026-08-12 (TEMP):** New sale — cashier/viewer may edit unit price freely (including below catalog). Flag: `SalesController::TEMP_ALLOW_SALESMAN_FREE_PRICE`. Set `false` to restore min-price floor. Salesman max qty unchanged.
- **2026-08-11:** Sale return scan accepts IMEIs originally sold to another party (create + edit UI + server validation). Credit stays on the selected return customer; stock/IMEI restore and sold-price lookup still use the original sale.
- **2026-07-30:** Item master **Salesman max qty** (`items.max_sale_qty`). Cashier/viewer blocked on sale create when invoice qty for an item exceeds the cap (client + `SaleValidator`). Admin/manager unrestricted. 0 = unlimited.
- **2026-07-29:** Warranty replacements can be **edited** (`?page=warranty&action=edit`). Changing faulty/replacement IMEI or replacement item reverses prior IMEI statuses and adjusts stock; branch stays fixed. Permission: `warranty` edit.
- **2026-07-29:** Sale return create locks stock (`FOR UPDATE`) before qty++. Void sale return no longer re-binds arbitrary in-stock IMEIs when links are incomplete — fails hard for `has_imei` lines; requires `return_src_sale` / `ref_id` to restore sold. Already-returned IMEI checks are branch-scoped. Purchase void still allows notes fallback for incomplete links but fails if serials remain unmatched.
- **2026-07-26:** Items list/edit — **Admin PIN** delete for unused catalog rows (`ItemController::delete`). Hard-delete only when no sales/purchases/IMEI/PO/returns/transfers/shipments/discounts/warranty/opening stock and stock qty is 0; otherwise mark Inactive on Edit.
- **2026-07-25:** Optional **`items.name_ar`** for bilingual sale receipts (thermal + A5). English `name` remains required; Arabic shown under the English line when set. Migration: `database/migrations/2026_07_25_items_name_ar.sql`.
- **2026-07-20:** **Strict sale IMEI** — cannot sell `has_imei` items without scanning every unit. Removed “Serial optional at sale” toggle; sale paths ignore `imei_optional`; migration clears existing flags. Prevents stock qty vs IMEI mismatch from unscanned sales.
- **2026-07-20:** Stock Audit paste: **Not in system** IMEIs offer **Register Missing** (`auditRegisterBulk`) so physical phones missing from the app can be added (fixes stock qty > IMEI count after paste).
- **2026-07-20:** Stock Audit reconcile page (`?page=imei&action=audit&item_id=…`) gained **Paste IMEIs** bulk mark-present (modal + `auditScanBulk`), same pattern as purchase IMEI scan station.
- **2026-07-19:** Stock List flags **IMEI over** (serials > Qty) as Mismatch, with KPI + “IMEI mismatch only” filter; over count links to `?page=imei&item_id=…`. IMEI History accepts `item_id` filter.
- **2026-07-15:** Added **Bank KYC Sales** report (`?page=reports&action=bankKyc`) — A4 formal PDF of period sales invoices with IMEI/serial numbers for bank KYC submission (branch-scoped; cancelled excluded).
- **2026-07-15:** Stock List hid **Samsung A17 5G 8GB/256GB GLOBAL NFC** because `is_active=0` despite qty 377 from PUR-000058. Item reactivated; Stock List now also shows inactive items with `quantity > 0` (Inactive badge).
- **2026-07-15:** Confirmed Stock List uses `stock.quantity` (not IMEI count). Hardened PO convert stock write; added purchase **Rebuild stock** + Stock List IMEI column so arrivals stay visible before serial scan.
- **2026-07-13:** Edit sale — Paste/scan IMEIs into new lines now saves correctly. `SalesController::update()` previously rejected all mandatory-IMEI `new_items` (message: use Add Item); it now validates serials, deducts stock, and links `sale_item_imei` like create/addItem.
- **2026-07-12:** PWA assets moved to `/assets/pwa/imei/` — live `/imei/*.webmanifest` and `/imei/sw.js` were rewritten to the warranty HTML page (icons 404), so install showed name without icon.
- **2026-07-12:** Public `/imei` PWA home-screen name set to **Warranty** (logo still iCARE artwork).
- **2026-07-12:** Public `/imei` warranty PWA home-screen icon set to iCARE logo (`imei/icons/`); short_name was `iCARE`.
- **2026-07-12:** Public `/imei` warranty page is a customer-only PWA (manifest + scoped service worker). Staff ERP is unchanged. Camera Permissions-Policy set to `(self)` so barcode scan works.
- **2026-07-11:** Item master form redesigned with ui-ux-pro-max Flat Design (slate + stock-green CTA, no shadows/gradients); Real Cost / Sale Price / Min stock one row; serial toggles side-by-side. Removed AED/USD reference price fields from UI; create/update no longer write `price_aed`/`price_usd` (existing DB values preserved for PO prefill). Sections: Identity / Pricing & stock / Serial tracking.
- **2026-07-11 (security):** Sale/return paths reject unknown IMEIs (no auto-register on sell/return). Mandatory IMEI items require scanned serials on return (no blind LIMIT restore).
- **2026-07-19:** Sale return credit always uses the **original sold unit price** from the customer’s sale line (`sale_item_imei` → `sale_items.unit_price`, or unique price on linked invoice). Catalog `items.sale_price` is never used; client-posted prices are overwritten server-side; price field is locked in UI.
- **2026-07-11:** Sale return — stock/IMEI restore unchanged; money side is party ledger credit only (does not recompute source invoice balance). See [party-balance-payments.md](party-balance-payments.md).
- **2026-07-09:** New sale invoice — bulk **Paste IMEIs** on the scan bar (same flow as edit invoice): validate list, then lookup each IMEI and auto-add/group line items.
- **2026-07-02:** ItemController fixes: items list and stock list now scope stock totals to the session branch (was summing all warehouses); `Item::create()`/`update()` now persist `imei_optional`, `price_aed`, and `price_usd` (form fields were silently dropped); `update()` checks affected rows and both save paths validate name + catch DB errors.
- **2026-07-02:** IMEI hardening (bug fixes): purchase IMEI scan/save endpoints validate the purchase belongs to the session branch (no cross-warehouse IMEI creation); `saveImei`/`savePurchaseImei` duplicate-check + insert now atomic (transaction + `FOR UPDATE`); IMEI clear tool no longer deletes IMEIs with sale/return history — they are marked `transferred` so `sale_item_imei`/`return_item_imei` audit trails survive; audit scan input raised to bulk limit (was silently truncated at 1000 chars, wrongly marking stock as transferred); IMEI index defaults to session branch; lifecycle timeline escapes DB values (stored XSS) and hides supplier/cost from users without purchases-view permission; public IMEI/statement rate limiters keyed on `REMOTE_ADDR` only (proxy headers spoofable; `TRUSTED_PROXY` flag in `config/app.php`) and made atomic via `flock` (`BaseController::ipRateLimited`).
- **2026-06-13:** Sale return scan — multiple source invoices allowed in one return; each IMEI validated against its own sale; all affected sale balances recomputed.
- **2026-06-13:** Purchase return create — failed save restores form draft (same as sale returns).
- **2026-06-13:** Return scan — reject mixed invoices/purchases and already-returned IMEIs at lookup; store validates IMEI count vs qty and sale-ref IMEI ownership.
- **2026-06-13:** Return create — failed save restores form draft (same as sales); client checks sale return qty limits before submit; scan Enter no longer wipes rows.
