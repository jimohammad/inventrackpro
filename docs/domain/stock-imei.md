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

### Transactions

- Critical paths use `beginTransaction()` + `SELECT ... FOR UPDATE` on `stock` rows.
- Never update stock outside a transaction for invoice-level operations.
- Returns must reverse the **original** stock movement and respect the document’s `warehouse_id`.

### Warehouse

- All stock queries in operational UI must scope to `Auth::warehouseId()` / `WarehouseScope`.
- See [warehouse-isolation.md](warehouse-isolation.md).

### IMEI

- `imei_records` tracks unit state; link tables connect to sale/purchase/return line items.
- Reports: `app/views/reports/customer_imei.php`, `app/controllers/IMEIController.php`.

## Code map

| Area | Location |
|------|----------|
| Sale stock | `app/models/Sale.php` |
| Purchase stock | `app/models/Purchase.php`, `PurchaseController.php` |
| Returns | `app/models/Return.php` |
| IMEI | `app/models/IMEI.php`, `IMEIController.php` |
| Sale validation | `app/services/SaleValidator.php` |
| Public warranty page | `app/views/public/imei_track.php` (`/imei`, `?page=imeitrack`) |
| Customer warranty PWA | `assets/pwa/imei/` (manifest, sw.js, icons); scope `/imei` only — do **not** put PWA files under `/imei/` (pretty-URL rewrite turns them into HTML) |

## Verify

- Manual: create sale → stock decreases; cancel/return → stock restored.
- Public PWA: open `https://iqbal.app/imei` on phone → Install / Add to Home Screen; lookups still hit the network (not stale cache).
- SQL: no negative qty (add audit if needed):

```sql
SELECT item_id, warehouse_id, quantity FROM stock WHERE quantity < 0;
```

## History

- **2026-07-12:** PWA assets moved to `/assets/pwa/imei/` — live `/imei/*.webmanifest` and `/imei/sw.js` were rewritten to the warranty HTML page (icons 404), so install showed name without icon.
- **2026-07-12:** Public `/imei` PWA home-screen name set to **Warranty** (logo still iCARE artwork).
- **2026-07-12:** Public `/imei` warranty PWA home-screen icon set to iCARE logo (`imei/icons/`); short_name was `iCARE`.
- **2026-07-12:** Public `/imei` warranty page is a customer-only PWA (manifest + scoped service worker). Staff ERP is unchanged. Camera Permissions-Policy set to `(self)` so barcode scan works.
- **2026-07-11:** Item master form redesigned with ui-ux-pro-max Flat Design (slate + stock-green CTA, no shadows/gradients); Real Cost / Sale Price / Min stock one row; serial toggles side-by-side. Removed AED/USD reference price fields from UI; create/update no longer write `price_aed`/`price_usd` (existing DB values preserved for PO prefill). Sections: Identity / Pricing & stock / Serial tracking.
- **2026-07-11 (security):** Sale/return paths reject unknown IMEIs (no auto-register on sell/return). Mandatory IMEI items require scanned serials on return (no blind LIMIT restore).
- **2026-07-11:** Sale return — stock/IMEI restore unchanged; money side is party ledger credit only (does not recompute source invoice balance). See [party-balance-payments.md](party-balance-payments.md).
- **2026-07-09:** New sale invoice — bulk **Paste IMEIs** on the scan bar (same flow as edit invoice): validate list, then lookup each IMEI and auto-add/group line items.
- **2026-07-02:** ItemController fixes: items list and stock list now scope stock totals to the session branch (was summing all warehouses); `Item::create()`/`update()` now persist `imei_optional`, `price_aed`, and `price_usd` (form fields were silently dropped); `update()` checks affected rows and both save paths validate name + catch DB errors.
- **2026-07-02:** IMEI hardening (bug fixes): purchase IMEI scan/save endpoints validate the purchase belongs to the session branch (no cross-warehouse IMEI creation); `saveImei`/`savePurchaseImei` duplicate-check + insert now atomic (transaction + `FOR UPDATE`); IMEI clear tool no longer deletes IMEIs with sale/return history — they are marked `transferred` so `sale_item_imei`/`return_item_imei` audit trails survive; audit scan input raised to bulk limit (was silently truncated at 1000 chars, wrongly marking stock as transferred); IMEI index defaults to session branch; lifecycle timeline escapes DB values (stored XSS) and hides supplier/cost from users without purchases-view permission; public IMEI/statement rate limiters keyed on `REMOTE_ADDR` only (proxy headers spoofable; `TRUSTED_PROXY` flag in `config/app.php`) and made atomic via `flock` (`BaseController::ipRateLimited`).
- **2026-06-13:** Sale return scan — multiple source invoices allowed in one return (same customer); each IMEI validated against its own sale; all affected sale balances recomputed.
- **2026-06-13:** Purchase return create — failed save restores form draft (same as sale returns).
- **2026-06-13:** Return scan — reject mixed invoices/purchases and already-returned IMEIs at lookup; store validates IMEI count vs qty and sale-ref IMEI ownership.
- **2026-06-13:** Return create — failed save restores form draft (same as sales); client checks sale return qty limits before submit; scan Enter no longer wipes rows.
