# Shop-to-shop stock transfer (stock only)

## Decisions

- **Iqbal Main** (iqbal.app) and **Lite** are two apps and two databases.
- Phones can move between them **without** a sale, purchase, payment, or party ledger.
- This is not the old warehouse transfer (Main ↔ Fahaheel in one database).
- To copy a **sales invoice** (prices + IMEIs) onto the other shop as a **purchase**, use the JSON file export/import — see [invoice-file-exchange.md](invoice-file-exchange.md). That path creates money and stock; Intershop does not.

## Rules

- Match products by **SKU** (barcode is fallback). The same SKU must exist on both shops. Do not auto-create items.
- Serial-tracked items: scanned IMEI count must equal quantity. Sender IMEIs must be `in_stock` / `returned`. Receiver inserts a new serial or re-stocks `sold` / `transferred`.
- Sender deducts `stock.quantity` first, then POSTs the peer. If the HTTP call fails, status is `failed` (stock already gone locally). **Retry** resends the same key. **Cancel** restores stock only when status is `pending` or `failed`.
- Receiver is **idempotent** on `idempotency_key`. A retry must not double stock.
- Session `warehouse_id` on the UI; API key must be scoped to **one** warehouse (or the shop’s default warehouse).
- No money, no `payments`, no party balance.

## Code map

| Area | Location |
|------|----------|
| UI | `IntershopController`, `app/views/intershop/` |
| Receive API | `api/v1/intershop.php` (`POST /api/?endpoint=intershop`) |
| Stock / IMEI | `IntershopTransfer`, `IMEI::markTransferredOut` / `attachFromIntershop` |
| Rebuild qty | `StockQuantityService` inbound received − outbound pending/sent/failed |
| Peer HTTP | `IntershopPeerClient` (`.env` `INTERSHOP_PEER_*`) |

## Go-live setup

On each shop’s `.env` (above `public_html`):

```
INTERSHOP_PEER_NAME=the other shop name
INTERSHOP_PEER_URL=https://the-other-shop.example
INTERSHOP_PEER_API_KEY=key-created-on-the-other-shop
```

On each database, one receive key (phpMyAdmin). Use a long random `api_key`. Main’s `.env` holds Lite’s key; Lite’s `.env` holds Main’s key.

```sql
INSERT INTO api_keys (app_name, api_key, permissions, is_active)
VALUES (
  'intershop-peer',
  'REPLACE_WITH_LONG_RANDOM_SECRET',
  JSON_OBJECT('intershop', JSON_ARRAY('write'), 'warehouse_id', 1),
  1
);
```

Same SKU on both item masters before the first send.

1. Same SKU on both item masters.
2. Send 1 serial from A → B: A qty −1, IMEI `transferred`; B qty +1, IMEI `in_stock`.
3. Retry the same send: B qty unchanged.
4. Failed send → Cancel: A qty and IMEI restored.
5. No new sale/purchase/payment rows.

## History

- **2026-09-07:** Stock-only link between Main and Lite.
- **2026-09-07:** Invoice JSON export/import is a separate money path (not Intershop). See [invoice-file-exchange.md](invoice-file-exchange.md).
