# Cash handed to admin

Admin is often away while the salesman takes customer payments. Those receipts already sit on **Main Cash** or the **bank**. This page only remembers which ones the admin has taken.

Open **Collect from salesman** (`?page=payments&action=collect`). Admin only.

## Rules

- Cashier and manager **Payment In** (including cash on a sale) waits until admin ticks it and presses **I collected these**.
- The admin’s own receipts are already with him and do not appear.
- Discounts, Payment Out, and cancelled receipts are left out.
- This branch only (`payments.warehouse_id` = session).
- Cash is the notes to count. Bank, card, cheque, and KNET are slips to confirm. Neither moves the account again. Party balance does not change.
- One receipt can be delivered once. **Undo** is the same day only (Asia/Kuwait) and returns those receipts to the open list.
- While the collection is active, the receipt cannot be edited or deleted. Undo it the same day, then edit or delete.
- If an invoice cancel voids the receipt later, history shows **cancelled later**.
- Receipts saved **before** the first opening of this page are already treated as with admin (`settings.key_name = payment_collection_from`). From that moment on, new cashier and manager receipts appear until admin ticks them.
- Salesman Payment In shows **Still to give admin** (cash and bank).

## Do not recreate Cash Custody

The old Finance → Cash Custody page was removed on **2026-09-09**. Do **not** recreate a Custody / Cash Custody / Safe cash account, `cash_handovers`, `cash_handover_payments`, or a `cash_custody` permission.

Till cash stays on **Main Cash** after Payment In. `AccountLedgerLoader::retireCashCustodyModule()` still drops those old tables on the Accounts page, which is why this feature uses `payment_collections` and `payment_collection_items`.

## Code map

| Area | Location |
|------|----------|
| Tables + collect / undo | `app/models/PaymentCollection.php` |
| Page | `PaymentController::collect` / `collectStore` / `collectUndo` |
| View | `app/views/payments/collect.php` |
| Lock edit/delete | `PaymentController` edit, update, delete; `Payment::deleteWithReversal` |
| Sidebar | Transactions → Collect from salesman (admin) |

## Verify

```sql
-- No second cash movement: a collection stores payment ids only.
SELECT c.id, c.collected_at, c.undone_at, i.payment_id, i.amount, i.bucket
FROM payment_collections c
JOIN payment_collection_items i ON i.collection_id = c.id
WHERE c.warehouse_id = 1
ORDER BY c.id DESC
LIMIT 20;

-- Open float (cashier/manager, not yet handed over)
SELECT py.id, py.payment_no, py.amount, u.name, u.role
FROM payments py
JOIN users u ON u.id = py.created_by AND u.role IN ('cashier', 'manager')
WHERE py.payment_type = 'in' AND py.status = 'active' AND py.warehouse_id = 1
  AND py.created_at >= (SELECT value FROM settings WHERE key_name = 'payment_collection_from' LIMIT 1)
  AND COALESCE(py.ref_type, '') <> 'discount'
  AND NOT EXISTS (
    SELECT 1 FROM payment_collection_items i
    JOIN payment_collections c ON c.id = i.collection_id AND c.undone_at IS NULL
    WHERE i.payment_id = py.id
  );
```

- Manual: cashier Payment In → salesman sees the orange total → admin collects cash and bank rows → totals go to zero → Main Cash and the customer Due are unchanged → same-day Undo restores the rows → next day Undo is hidden.

## History

### 2026-10-08 — Select a whole group
On Collect from salesman, **Select all** ticks that person’s cash and bank receipts together. **All cash** and **All bank** tick one group. Unticking one receipt clears that group.

### 2026-09-26 — Handover memory
Admin records which cashier/manager receipts he took. No Custody account and no extra bank posting.

### 2026-09-09 — Removed
Business no longer uses a separate safe account. Feature, permissions, and auto-created Custody account deleted.

### 2026-08-22 — Cash custody (former)
Owner is often away when the salesman collects cash. Two-step page counted till receipts then transferred Main Cash → Custody. Retired.
