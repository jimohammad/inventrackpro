# BUSINESS_RULES.md

## Warehouse
- App is operated as single warehouse.
- Do not remove warehouse_id columns.
- Default active warehouse is auto-selected.
- Warehouse switching UI is hidden.

## Stock
- Sales decrease stock.
- Purchases increase stock.
- Sale returns increase stock.
- Cancel sale restores stock only if no approved return exists.
- Stock changes must be inside transaction.

## IMEI
- IMEI items should have IMEI count equal to quantity unless item allows optional IMEI.
- Sold IMEI status = `sold`.
- Returned/available IMEI status = `in_stock` or `returned`.
- Do not sell IMEI from another warehouse.

## Payments
- Customer receipt = `payment_type = in`.
- Supplier payment = `payment_type = out`.
- Party balances must use directional payment logic.

## Returns
- Returns should reverse original sale stock movement.
- Linked sale returns reduce sale balance.