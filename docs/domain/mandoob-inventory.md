# Mandoob inventory counts

Physical van / agent stock checks on a reminder schedule (default every 3 months).

## Decisions

- **Inventory done** records a dated count for that mandoob, then resets next due to **count date + interval**.
- Counted items are a **snapshot** of what the mandoob had on that date. They do **not** change warehouse `stock` or `imei_records`.
- IMEIs on a count are optional notes (what was seen), not a serial movement.
- Saving a count **unpauses** the schedule if it was paused.
- All rows are scoped to the session warehouse.

## Rules

- Must save `warehouse_id` from `Auth::warehouseId()`.
- Must not post stock, payments, or IMEI status from a mandoob count.
- List / history / record queries must filter `warehouse_id`.
- Interval is 1–24 months (default 3).
- Items on a count are optional: date-only still resets the countdown.
- **Inventory done** opens a date reset page (no modal). Saving replaces last count and sets next due = count date + interval. Counted items are optional via **Record counted items**.

## Code map

| Piece | Location |
|-------|----------|
| List / pause / resume | `MandoobInventoryController`, `app/views/mandoob_inventory/index.php` |
| Reset date | `reset()` / `record_count()`, `app/views/mandoob_inventory/reset.php` |
| Record items | `record()` / `record_count()`, `app/views/mandoob_inventory/record.php` |
| History | `history()`, `app/views/mandoob_inventory/history.php` |
| Tables | `mandoob_inventory_schedules`, `mandoob_inventory_history`, `mandoob_inventory_count_items` |
| Auto-schema | `MandoobInventoryController::ensureSchema()` + `database/migrations/2026_08_16_mandoob_inventory_count_items.sql` |

## Verify

1. Open **Mandoob Inventory** → **Inventory done** on a row that already has a last count.
2. Confirm the current last count is shown. Set the real inventory date → **Reset last count & next due**.
3. List should show the new last count and next due (date + interval). Overdue clears if the new due is in the future.
4. Optional: **Record counted items** to save item lines. Stock / IMEI must not change.

```sql
SELECT warehouse_id, COUNT(*) AS n FROM mandoob_inventory_count_items GROUP BY warehouse_id;
-- Fahaheel (warehouse_id = 3) must be 0 while that branch is inactive.
```

## History

### 2026-08-16 — Reset inventory date
The old Inventory done modal did not save. **Inventory done** now opens a full-page date reset (current last count shown, new date, next due preview) so already-completed counts can replace the stale date. Item recording remains on `action=record`.

### 2026-08-16 — Record counted items
Inventory done opened a date-only modal. Staff needed a list of what was counted. Record page now saves item lines + optional IMEIs on `mandoob_inventory_count_items`.
