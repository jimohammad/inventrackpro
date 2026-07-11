# Fahaheel Branch Go-Live Checklist

Use this when Fahaheel moves from **legal-only** (`is_active = 0`) to a **real second operational branch** in InvenTrack Pro.

**Policy (see also `.cursor/rules/warehouse-isolation.mdc`):** Main and Fahaheel are fully isolated in the ERP—no mixed balances, reports, or statements. Only **stock transfers** intentionally cross branches.

---

## Phase 0 — Decision (before any ERP changes)

- [ ] Confirm Fahaheel is a **second operational shop** (stock, sales, cash), not legal name only.
- [ ] Decide **party model**:
  - **A)** New Fahaheel-only customers (recommended) — new parties with home warehouse = Fahaheel.
  - **B)** Same legal customer at both branches — **two party records** (different codes) *or* one record with strict “only invoice in the branch you’re logged into.”
- [ ] Decide **cash/bank**: shared accounts vs **separate accounts** per branch (e.g. “Fahaheel Cash”).
- [ ] Decide whether **stock** starts empty at Fahaheel or moves from Main via **stock transfers** only.

---

## Phase 1 — Database & warehouse record

- [ ] **Backup** full database (export from phpMyAdmin / Hostinger).
- [ ] Create or reactivate warehouse row, e.g. **“Fahaheel Branch”**:
  - `is_active = 1`
  - `is_default = 0` (keep **Main** as default)
  - Notes: operational start date
- [ ] Note the new **`id`** (may be 3 again or a new id if the old row was deleted—both are fine; the app uses ids dynamically).
- [ ] Run full dependency audit (all must be **0** before first go-live sale, except new setup you add intentionally):

```sql
SET @fid = <fahaheel_warehouse_id>;

SELECT 'sales' AS t, COUNT(*) AS cnt FROM sales WHERE warehouse_id = @fid
UNION ALL SELECT 'purchases', COUNT(*) FROM purchases WHERE warehouse_id = @fid
UNION ALL SELECT 'payments', COUNT(*) FROM payments WHERE warehouse_id = @fid
UNION ALL SELECT 'returns', COUNT(*) FROM `returns` WHERE warehouse_id = @fid
UNION ALL SELECT 'expenses', COUNT(*) FROM expenses WHERE warehouse_id = @fid
UNION ALL SELECT 'stock', COUNT(*) FROM stock WHERE warehouse_id = @fid
UNION ALL SELECT 'parties', COUNT(*) FROM parties WHERE warehouse_id = @fid
UNION ALL SELECT 'warehouse_users', COUNT(*) FROM warehouse_users WHERE warehouse_id = @fid
UNION ALL SELECT 'stock_transfers', COUNT(*) FROM stock_transfers
  WHERE from_warehouse_id = @fid OR to_warehouse_id = @fid
UNION ALL SELECT 'imei_records', COUNT(*) FROM imei_records WHERE warehouse_id = @fid
UNION ALL SELECT 'purchase_orders', COUNT(*) FROM purchase_orders WHERE warehouse_id = @fid
UNION ALL SELECT 'mandoob_schedules', COUNT(*) FROM mandoob_inventory_schedules WHERE warehouse_id = @fid;
```

Use the same query in Phase 1 and Phase 7 below to confirm the inactive branch has no stray transactions.

---

## Phase 2 — Users & access

- [ ] In **Settings → Warehouses**, assign staff who may work at Fahaheel (`warehouse_users`).
- [ ] Restrict who can switch branches (admin vs branch-only staff).
- [ ] Each user **logs out and in**, picks **Fahaheel** on the warehouse picker (or auto-select if they only have one branch).
- [ ] Confirm inactive warehouse in session is cleared and user is sent to the picker (`Auth::ensureOperationalWarehouse()` in `index.php`).

---

## Phase 3 — Chart of accounts (optional but recommended)

- [ ] Create Fahaheel-specific accounts if you want separate cash drawers:
  - e.g. “Fahaheel Cash”, “Fahaheel Bank”
- [ ] Document rule: **only use those accounts** when session = Fahaheel.
- [ ] Main staff continue using Main accounts only when session = Main.

---

## Phase 4 — Parties (customers & suppliers)

- [ ] While logged in as **Fahaheel**, create new parties with **home warehouse = Fahaheel** (not NULL).
- [ ] Do **not** reuse Main party codes for different people; use new `party_code` if needed.
- [ ] Set **opening balance** only for Fahaheel customers who truly open with balance **at that branch** (opening applies when home warehouse matches branch; see `WarehouseScope::openingBalanceForParty`).
- [ ] For suppliers used only at Fahaheel, same rule—create under Fahaheel session.
- [ ] Regenerate or copy **public statement links** (`/s/{token}`) for Fahaheel customers after they exist (links are branch-scoped).
- [ ] **Avoid** leaving `parties.warehouse_id = NULL` on new records (NULL parties can appear in **both** branch lists).

---

## Phase 5 — Stock & IMEI

- [ ] Log in → select **Fahaheel**.
- [ ] **Opening stock** (purchases or opening-stock flow) with `warehouse_id` = Fahaheel on every document.
- [ ] IMEI items: scan/register at Fahaheel only while on that branch.
- [ ] If moving from Main: use **Stock transfer** Main → Fahaheel (do not duplicate purchase on both branches).
- [ ] Spot-check **stock report** and IMEI list—only Fahaheel qty when Fahaheel session is active.

---

## Phase 6 — First live transactions (pilot day)

- [ ] One **test sale** + one **test payment** (small amount).
- [ ] Void/cancel test docs if you use test data, or keep as real with clear notes.
- [ ] One **purchase** (if applicable) at Fahaheel.
- [ ] Confirm on **Party Master** (Fahaheel session): balance matches expectation.
- [ ] Confirm on **Reports → customer statement** (Fahaheel): same balance, no Main invoices mixed in.
- [ ] Open **public statement** `https://<domain>/s/{token}` for one Fahaheel customer—only Fahaheel lines.

---

## Phase 7 — Main branch regression check

- [ ] Switch session to **Main**.
- [ ] Party list / balances **unchanged** from before go-live (no Fahaheel totals in Main dashboard).
- [ ] Re-run the Phase 1 branch transaction count query—Fahaheel counts only reflect new Fahaheel activity; Main history unchanged.

---

## Phase 8 — Training & daily rules

| Rule | Detail |
|------|--------|
| Always check branch | Warehouse name in UI = correct branch before any sale |
| Every document | Sale, purchase, payment, return, expense saves **current** session `warehouse_id` |
| No cross-branch fixes | Do not record a Fahaheel sale while logged into Main |
| Parties | Create customers in the branch where they trade |
| Stock | No manual copy between branches—use **transfer** only |
| Statements | Send customer link generated while on **their** branch |

---

## Phase 9 — Ongoing monitoring (first 2 weeks)

- [ ] Weekly: run Phase 1 audit SQL—no accidental wrong `warehouse_id`.
- [ ] Review payments with `warehouse_id IS NULL`—backfill to correct branch if any appear (see audit script step 5).
- [ ] Compare Fahaheel vs Main in **daily summary** email—each branch only lists its own sales.
- [ ] API / mobile: ensure requests include correct `warehouse_id` for Fahaheel users/devices.

---

## Phase 10 — What you do **not** need

- [ ] No app redeploy solely for “activating” a warehouse row (if branch isolation code is already deployed).
- [ ] No merge of Main + Fahaheel in operational reports.
- [ ] No import of historical Fahaheel ERP data (audit showed zero transactions on inactive branch).

---

## Quick reference

**Same ERP, switch branch at login** → separate stock, invoices, payments, and balances per branch.

**Separate party lists** require assigning **home warehouse** per party and avoiding NULL `warehouse_id` on new records.

**Separate “company feel”** for cash: create branch-specific **accounts** and train staff.

---

## Related files

| File | Purpose |
|------|---------|
| `docs/domain/warehouse-isolation.md` | Branch isolation rules and verify SQL |
| `.cursor/rules/warehouse-isolation.mdc` | Developer rules for branch isolation |
| `CLAUDE.md` | Warehouse isolation summary |

---

*Last updated: 2026-06-04*
