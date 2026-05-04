# CHANGELOG_AI.md

## 2026-05-04
### Return Module
- Added warehouse resolver.
- Improved return item validation.
- Added IMEI warehouse checks.
- Fixed return listing script close.

### Sale Module
- Added warehouse resolver.
- Fixed sale edit warehouse update bug.
- Added payment row lock with FOR UPDATE.

### Purchase Order Module
- Added warehouse access helper.
- Added row locks for mark paid/convert/cancel.
- Fixed unpaid converted purchase status from partial to confirmed.

### Warehouse
- Enabled safe single-warehouse mode.
- Auto-select default warehouse.
- Hid warehouse switch UI from layout.