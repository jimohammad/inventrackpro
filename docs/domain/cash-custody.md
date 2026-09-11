# Cash custody — retired

This module was removed on **2026-09-09**. Do **not** recreate a Custody / Cash Custody cash account, `cash_handovers` tables, or a Finance → Cash Custody page.

Till cash stays on **Main Cash** after Payment In. There is no later count-into-safe step.

Cleanup (first Accounts page load, or run the SQL once):

- Drop `cash_handover_payments` and `cash_handovers`
- Delete `permissions` rows with `module = 'cash_custody'`
- Move remaining **Custody** balance to **Main Cash**, then delete the account when it only had Main Cash transfers

See `AccountLedgerLoader::retireCashCustodyModule()`.

## History

### 2026-09-09 — Removed
Business no longer uses a separate safe account. Feature, permissions, and auto-created Custody account deleted.

### 2026-08-22 — Cash custody (former)
Owner is often away when the salesman collects cash. Two-step page counted till receipts then transferred Main Cash → Custody. Retired.
