# Dump Credit (unrepairable sold units)

Party brings a **sold** device that cannot be repaired. We **dump** it (do not restock) and **credit the party**. Parts harvest is not in this version.

The ERP started **April 2026**. Units sold before that often have **no IMEI in the app**. Those can still be dumped as **older / not in app**.

## Decisions

- **In-app IMEI:** status must be **`sold`** on this branch. Credit = **full original sold unit price** (same resolver as sale returns). Catalog `sale_price` is never used for these lines.
- **Not in app (pre-April 2026):** IMEI is not in `imei_records`. Staff pick the **catalog model** and type the **credit amount** (catalog price is only a default they can change). A `dumped` IMEI row is created so the serial cannot be sold later until the dump is voided.
- Credit the **party typed and selected on the form** (the account that is reduced). Search is AJAX (same as New Sale) — do not load every party into a dropdown. IMEI scan does not pick the party. Original buyer is shown as info only for in-app units.
- In-app IMEI status becomes **`dumped`**. Never sellable, transferable, or sale-returned until the dump is voided.
- Phone **stock qty does not increase**. The sale already deducted qty (in-app), or the unit was never in ERP stock (pre-app). Dump does not add qty back.
- No cash/bank payment row. Ledger source is `device_dumps` (like `returns`, not `payments.ref_type`).
- Parts dismantle / spare-part stock: **later**. V1 is dump + credit only.
- Cashiers have no Dump Credit permission by default. Admin/manager get view+add; only admin can void.

## Rules

- Must save `warehouse_id` from `Auth::warehouseId()`.
- List / view / void scoped to that branch.
- Do not dump `in_stock`, `returned`, `defective`, or already `dumped` serials as “not in app” — if the IMEI exists, use the in-app path only.
- Do not mix dump with a sale return on the same in-app IMEI (status would not be `sold` after a return).
- Do not cancel a sale that still has a dumped IMEI — void the dump first.
- Void dump: in-app IMEI `dumped` → `sold` again; **not-in-app** IMEI row is **deleted** (so it can be purchased later). Party credit reversed. Still **no** stock change.
- Dump credits subtract from party Due like sale returns (folded into the `sale_returns` arm of the balance UNION). Statements show type **Dump**.
- Does **not** change `sales.balance` on the original invoice (in-app lines). Pre-app lines have no invoice.

## Code map

| Piece | Location |
|-------|----------|
| List / create / view / void / IMEI lookup / item search | `DumpController`, `app/views/dumps/` |
| Create / void / next no / pre-app IMEI insert | `DeviceDump` |
| Auto-schema + `is_legacy` + IMEI `dumped` status | `DeviceDump::ensureSchema()` |
| Party Due / statement / Party Master timeline | `Party.php` (`dumpCreditsReady`, dump UNION arm, `txn_type = dump`) |
| Permission | `dumps` (view / add / delete for void) |
| Nav | Transactions → **Dump Credit** |

## Verify

1. Sell an IMEI-tracked phone. Open **Dump Credit → New Dump**, type the party name and pick from the list, scan that IMEI → sold price fills. Save.
2. Party Master Due drops by that sold price. Party statement shows **Dump** `DUMP-00000x` as credit.
3. Stock List qty for that SKU is unchanged (still down from the sale). IMEI History status **dumped**. IMEI Lifecycle shows Dump credit.
4. Try New Sale / Sale Return / Warranty Replace / purchase buy-back scan of that IMEI → blocked.
5. Try cancel the original invoice → blocked until the dump is voided.
6. Admin **Void dump** → Due restored; IMEI `sold` again; can warranty-replace or sale-return as before.
7. Scan an IMEI that is **not** in the app → panel asks for model + credit amount. Save → Due drops by that amount; IMEI status **dumped**; stock qty unchanged. Void → IMEI gone from registry; Due restored.
8. SQL (inactive Fahaheel must stay 0): `SELECT COUNT(*) FROM device_dumps WHERE warehouse_id = 3;`

## History

- **2026-09-03:** In-app dump save failed with “Could not mark IMEI as dumped” because `imei_records.status` ENUM had no `dumped` value (MySQL stored blank and the transaction rolled back). `DeviceDump::ensureSchema()` now appends `dumped` via `PDO::exec` on first Dump Credit page load (prepared ALTER is rejected on Hostinger).
- **2026-08-29:** Added Dump Credit (sold units only, full sold price, no restock, no parts yet). Party field is type-to-search, not a full dropdown.
- **2026-08-29:** Older devices not in the app (pre-April 2026) can be dumped: pick model + credit amount; IMEI registered as `dumped`; void deletes that registry row.
