# Sales invoice file exchange (shop to shop)

## Decisions

- Two shops (e.g. iqbal.app and Lite) are **separate apps and databases**.
- A **file** moves a sales invoice: shop A exports JSON; shop B imports it as a **purchase** (stock, IMEIs, supplier payable).
- This is **not** Intershop. Intershop moves stock with no sale, purchase, or party ledger. See [intershop-stock.md](intershop-stock.md).

## Rules

- Format: `iqbalerp.sale_invoice` version `1`. Filename `{invoice_no}.iqbal.json`.
- Match catalog lines by **SKU** (barcode fallback). Unique match only. **Do not auto-create items.** Set SKU on Item Master (Identity); it is optional in the catalog but required on every exported sale line.
- Export: cancelled sales are blocked. Every line needs a SKU. Serial-tracked lines need IMEI count = qty.
- Import: staff **picks the supplier** on shop B (party IDs differ). Source invoice number → `supplier_invoice_no`. Notes record `Imported from {shop} {invoice_no}`.
- Purchase cost per line is **net unit** = file `line_total / quantity` (sale line discount included). Header discount copies to `purchases.discount`. **No payment** on import (`paid_amount = 0`).
- IMEIs attach with `IMEI::attachToPurchase` in the same purchase transaction (new serial or buy-back of `sold` / `transferred`). Failure rolls back the purchase.
- Duplicate: warn if this warehouse already has a non-cancelled purchase with the same `supplier_invoice_no`. Staff may override.
- Session `warehouse_id` on the new purchase. Keep the open-PO duplicate-payment guard.

## Code map

| Area | Location |
|------|----------|
| JSON build / parse / SKU match | `SaleInvoiceFile` |
| Export | `SalesController::exportInvoice`, sales invoice view |
| Import UI | `PurchaseController::importInvoice` / `importInvoiceStore`, `app/views/purchases/import_invoice.php` |
| Save + IMEI | `Purchase::createFullPurchase` → `IMEI::attachToPurchase` |

## Verify

1. Shop A: open a sale with IMEIs → **Export invoice** → JSON has SKUs, qty, prices, serials.
2. Cancelled sale: no export button; direct URL is rejected.
3. Shop B: item missing SKU → preview lists it and Save stays disabled.
4. Shop B: same SKUs exist → pick supplier → save → PUR created, stock up, IMEIs `in_stock`, supplier due = file grand total, `supplier_invoice_no` = shop A invoice.
5. Import the same file again → warning; override creates a second purchase.
6. Accessory-only sale (no IMEIs) still imports.

## History

- **2026-09-07:** Item Master SKU field (form was missing it; `items.sku` was already stored). Saved uppercase.

- **2026-09-07:** File export of a sales invoice and import as a purchase on another shop (`SaleInvoiceFile`).
