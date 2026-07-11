# Purchase orders & import logistics

## Decisions

- **AED / USD PO** = overseas import — supplier price recorded in foreign currency; KWD column is the real cost basis; freight/logistics applied via **Import Logistics**.
- **KWD PO** = **local purchase** — may still be linked to Import Logistics when extra charges apply (e.g. local delivery). Otherwise convert directly to Purchase Invoice.
- Foreign price on PO is a **manual record/reminder** only — no automatic FX conversion (`exchange_rate` is typically 1).
- Payments on PO are recorded in **KWD only** (`paid_kwd`); `paid_foreign` is not used for settlement.

## Rules

### PO lifecycle

| Status | Meaning |
|--------|---------|
| `draft` | Created, not paid to supplier |
| `paid` | Supplier advance paid (KWD from account) |
| `converted` | Purchase invoice created, stock in |
| `cancelled` | Void |

### Currency

| Currency | Workflow |
|----------|----------|
| AED, USD | May link to Import Logistics shipment → receive → landed cost → convert |
| KWD | May link to Import Logistics when charges apply; otherwise convert directly |

### Import Logistics

- POs with status `draft` or `paid` (any currency including KWD) appear in shipment form.
- One PO cannot be on two open (non-`applied`) shipments.
- Shipment `applied` = goods received in Kuwait; purchase invoices created via `LandedCostAllocator` / `PurchaseOrderConverter`.

### Convert without shipment

- Allowed for import POs when no open shipment — user confirms “convert only (no shipment)”.
- Standard path for **KWD PO**.

## Code map

| Area | Location |
|------|----------|
| PO create/edit | `app/views/purchase_orders/create.php`, `edit.php` |
| PO controller | `app/controllers/PurchaseOrderController.php` |
| PO picker | `LandedCostController::purchaseOrdersForForm()`, `assertPoLinkable()`, `poItems()` |
| Convert PO → purchase | `app/services/PurchaseOrderConverter.php` |
| Reverse purchase → PO | `PurchaseController::reverseToPo()`, `PurchaseOrderController::reverseToPo()`, `Purchase::reverseToPoWithReversals()` |
| Allocate landed cost | `app/services/LandedCostAllocator.php` |
| Import shipment UI | `app/views/purchases/landed_cost_form.php` |

## Verify

KWD POs on shipments should only be `draft` or `paid` (not `converted`):

```sql
SELECT spo.shipment_id, spo.po_id, po.po_no, po.status
FROM shipment_purchase_orders spo
JOIN purchase_orders po ON po.id = spo.po_id
WHERE po.currency = 'KWD' AND po.status NOT IN ('draft', 'paid');
```

Result should be empty.

## History

### 2026-06-11 — Party balance vs PO prepaid

- `purchase_order` payments (PO paid, awaiting goods) are **excluded** from party Net Balance — they are prepaid, not AP.
- When a PO converts, party balance uses **PO KWD total** (`subtotal_kwd + other_charges_kwd`), not purchase invoice `grand_total` if logistics inflated the invoice.

### 2026-06-11 — KWD local PO + import exclusion

- Added KWD to PO currency dropdown for local suppliers.
- Excluded `currency = 'KWD'` from Import Logistics picker and `assertPoLinkable()`.
- Rationale: local purchases have no HK→DXB→Kuwait freight legs.

### 2026-07-06 — Reverse purchase to PO

- Added **Reverse to PO** on purchase detail and converted PO screens.
- Cancels the linked purchase (stock + payments reversed), reopens the source PO to `draft`/`paid`.
- Blocked when purchase is on an **applied** import shipment — undo shipment receive first.

### 2026-06-30 — Allow KWD POs on Import Logistics

- Removed KWD exclusion from `purchaseOrdersForForm()`, `poItems()`, and `assertPoLinkable()`.
- Local KWD POs can now be added to shipments when logistics charges apply.
