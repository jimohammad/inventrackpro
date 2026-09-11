# Apps order requests (prior confirmation)

Customer order requests from **iqbal.app/apps** before a sale is created.

## Decisions

- Public customers can **request** an order for staff prior confirmation (availability / readiness).
- Approval does **not** create a sale invoice — staff create the sale separately when the customer pays / picks up.
- Scoped to the operational warehouse (default active branch) when the request is created.
- Staff desktop notifications require the ERP open in Chrome/Edge with notification permission.
- Customer reply notifications use the apps status page / PWA (browser notification if allowed).

## Rules

- Must validate CSRF on public submit.
- Must rate-limit by IP and phone.
- Must not mix warehouse scopes on staff list/detail.
- Must not auto-post stock or payments from approval.
- Public item picker lists only **in-stock** items for the operational warehouse; qty cannot exceed available stock.

## Code map

| Piece | Location |
|-------|----------|
| Public form / status | `AppsOrderController`, `app/views/public/apps_order.php` |
| Hub tile | `app/views/public/apps_hub.php` |
| Staff list / decide | `OrderRequestController`, `app/views/order_requests/` |
| Table | `order_requests` (`OrderRequest::ensureTable`, migration `2026_07_29_order_requests.sql`) |
| ERP poll / desktop toast | `assets/js/app.js` → `?page=orderrequests&action=pendingJson` |

## Verify

1. Open https://iqbal.app/order (or Apps → Request Order) → submit a test request.
2. With ERP open (sales access), confirm desktop notification / bell badge.
3. Approve in **Order Requests** → customer status page shows Approved and (if allowed) a notification.
4. Confirm no sale/stock row was created by approval alone.

## History

### 2026-08-23 — Faster ERP poll
`pendingJson` no longer runs `CREATE TABLE IF NOT EXISTS` on every badge poll. Count + latest id are one query; table create only retries if the table is missing.

### 2026-07-30 — Removed free-text other items
Public form no longer has an “Other items” textarea; orders must use the stock item picker only.

### 2026-07-29 — Prior confirmation orders
Added public `/apps/order` + ERP Order Requests with bidirectional browser notifications.
