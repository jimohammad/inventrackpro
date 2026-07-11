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

## Verify

- Manual: create sale → stock decreases; cancel/return → stock restored.
- SQL: no negative qty (add audit if needed):

```sql
SELECT item_id, warehouse_id, quantity FROM stock WHERE quantity < 0;
```

## History

- **2026-07-11 (security):** Sale/return paths reject unknown IMEIs (no auto-register on sell/return). Mandatory IMEI items require scanned serials on return (no blind LIMIT restore).
- **2026-07-11:** Sale return — stock/IMEI restore unchanged; money side is party ledger credit only (does not recompute source invoice balance). See [party-balance-payments.md](party-balance-payments.md).
- **2026-07-09:** New sale invoice — bulk **Paste IMEIs** on the scan bar (same flow as edit invoice): validate list, then lookup each IMEI and auto-add/group line items.
- **2026-07-02:** ItemController fixes: items list and stock list now scope stock totals to the session branch (was summing all warehouses); `Item::create()`/`update()` now persist `imei_optional`, `price_aed`, and `price_usd` (form fields were silently dropped); `update()` checks affected rows and both save paths validate name + catch DB errors.
- **2026-07-02:** IMEI hardening (bug fixes): purchase IMEI scan/save endpoints validate the purchase belongs to the session branch (no cross-warehouse IMEI creation); `saveImei`/`savePurchaseImei` duplicate-check + insert now atomic (transaction + `FOR UPDATE`); IMEI clear tool no longer deletes IMEIs with sale/return history — they are marked `transferred` so `sale_item_imei`/`return_item_imei` audit trails survive; audit scan input raised to bulk limit (was silently truncated at 1000 chars, wrongly marking stock as transferred); IMEI index defaults to session branch; lifecycle timeline escapes DB values (stored XSS) and hides supplier/cost from users without purchases-view permission; public IMEI/statement rate limiters keyed on `REMOTE_ADDR` only (proxy headers spoofable; `TRUSTED_PROXY` flag in `config/app.php`) and made atomic via `flock` (`BaseController::ipRateLimited`).
- **2026-06-13:** Sale return scan — multiple source invoices allowed in one return (same customer); each IMEI validated against its own sale; all affected sale balances recomputed.
- **2026-06-13:** Purchase return create — failed save restores form draft (same as sale returns).
- **2026-06-13:** Return scan — reject mixed invoices/purchases and already-returned IMEIs at lookup; store validates IMEI count vs qty and sale-ref IMEI ownership.
- **2026-06-13:** Return create — failed save restores form draft (same as sales); client checks sale return qty limits before submit; scan Enter no longer wipes rows.
