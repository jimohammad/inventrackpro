# Discounts & payment ref_type

## Decisions

- `payments.ref_type` links a payment row to its business source.
- Changing or adding a `ref_type` requires searching **all usages** (balance SQL, account balance, reports, net worth).
- Discounts reduce customer balance via a payment with `ref_type = 'discount'` — they are **not** warehouse-scoped in `customer_discounts`, but the payment row carries `warehouse_id`.

## Rules

### ref_type reference

| ref_type | Meaning | Balance impact |
|----------|---------|----------------|
| `sale` | Receipt/payment against sale invoice | Customer: reduces owed (with `payment_type`) |
| `purchase` | Payment against purchase invoice | Supplier: reduces we-owe |
| `purchase_order` | Advance paid on PO (before convert) | **Not in party balance** until converted (prepaid asset); account out only |
| `expense` | Expense payment | Account only (not party balance union) |
| `discount` | Customer discount granted | Customer: reduces owed (always `payment_type` in) |
| `shipment_cost` | Legacy/generic shipment cost | Supplier-side payment family |
| `shipment_freight_hk` | Freight HK→DXB leg paid | Supplier balance + account |
| `shipment_packing_dxb` | Packing DXB leg | Same |
| `shipment_freight_dxb` | Freight DXB→Kuwait leg | Same |
| `shipment_partner` | Partner profit leg | Same |

### Import accruals (not payments until paid)

- `import_payable_accruals` — open vendor bills created on shipment receive.
- Cleared when payment linked via `LandedCostPaymentLinker` / `ImportPayableAccrualService`.
- Leg → ref_type mapping in `ImportPayableAccrualService::REF_TYPE_TO_LEG`.

### PO convert payment flip

- On PO convert: `purchase_order` payment ref may update to `purchase` pointing at new invoice (`PurchaseOrderConverter`).
- **Never** leave duplicate active payments for same PO (both `purchase_order` and `purchase`).

### Discount delete safety

- `DiscountController` deletes only `ref_type = 'discount'` rows (by `payment_id` or locked lookup).
- Discount payments are excluded from some account views (`ref_type != 'discount'`).

## Code map

| Area | Location |
|------|----------|
| Discount CRUD | `app/controllers/DiscountController.php` |
| Party balance ref filter | `app/models/Party.php` — `batchBalanceUnionSql()` |
| Account balance | `app/services/AccountBalanceService.php` |
| PO payment flip | `app/services/PurchaseOrderConverter.php` |
| Import accruals | `app/services/ImportPayableAccrualService.php` |
| Shipment payments | `app/controllers/LandedCostController.php` |

## Verify

No duplicate active payments for the same PO after conversion:

```sql
SELECT po.id, po.po_no,
       SUM(CASE WHEN p.ref_type = 'purchase_order' AND p.status = 'active' THEN 1 ELSE 0 END) AS po_payments,
       SUM(CASE WHEN p.ref_type = 'purchase' AND p.status = 'active' THEN 1 ELSE 0 END) AS purchase_payments
FROM purchase_orders po
LEFT JOIN payments p ON p.ref_id = po.id AND p.ref_type IN ('purchase_order', 'purchase')
WHERE po.converted_to IS NOT NULL
GROUP BY po.id, po.po_no
HAVING po_payments > 0 AND purchase_payments > 0;
```

Result should be empty. When touching `ref_type`, search all usages:

```bash
rg "ref_type" app/ database/ --glob "*.php" --glob "*.sql"
```

## History

- **2026-07-02 (fix):** `DiscountController` hardening — delete/update fast-path (via `customer_discounts.payment_id`) now locks the linked payment with `FOR UPDATE`, scopes it to `warehouse_id = Auth::warehouseId()`, and fails loudly if the row is missing or its `ref_type` changed (previously it deleted the discount row silently, potentially orphaning a balance-affecting payment cross-branch). `discount_no` sequence is now generated inside the transaction with `FOR UPDATE` (was racy). `party_id` is validated against active customer parties on store/update.
- **2026-06-28 (fix):** Discount payments now included in `Party::getPartyStatementTransactions()` and `batchBalanceUnionSql()` — previously excluded despite UI/report summary expecting `txn_type = 'discount'`.

_(Add dated notes when fixing ref_type / discount bugs.)_
