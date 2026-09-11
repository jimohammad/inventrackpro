# Iqbal ERP — Business Domain Rules

Human- and AI-readable **business brain** for money, stock, and branch logic.  
Graphify maps **code structure**; these files map **what must stay true**.

## When to read

| You are changing… | Read first |
|-------------------|------------|
| Reports, balances, statements, payments, wholesale/retail customers | [party-balance-payments.md](party-balance-payments.md) |
| Warehouse picker, branch reports, public links | [warehouse-isolation.md](warehouse-isolation.md) |
| Purchase orders, import shipments, landed cost | [purchase-orders-import.md](purchase-orders-import.md) |
| Stock, returns, IMEI | [stock-imei.md](stock-imei.md) |
| Shop-to-shop stock (Main ↔ Lite) | [intershop-stock.md](intershop-stock.md) |
| Sales invoice JSON → purchase on another shop | [invoice-file-exchange.md](invoice-file-exchange.md) |
| Discounts, payment `ref_type` | [discounts-ref-types.md](discounts-ref-types.md) |
| Accounts, net worth, freight payables | [accounts-landed-cost.md](accounts-landed-cost.md) |
| Public apps order confirmation | [apps-order-requests.md](apps-order-requests.md) |
| Mandoob van counts | [mandoob-inventory.md](mandoob-inventory.md) |
| Employee records (HR) | [employees.md](employees.md) |
| Dump credit (unrepairable sold units) | [device-dumps.md](device-dumps.md) |
| Salesman invoice edit (admin unlock) | [stock-imei.md](stock-imei.md) |

## Template (for new rules)

Each domain file uses:

1. **Decisions** — why the business works this way  
2. **Rules** — must / must-not  
3. **Code map** — where enforced in PHP  
4. **Verify** — SQL audit or manual check  

## After code changes

1. Update the matching domain file if behavior changed  
2. Run the **Verify** checks in the matching domain file (manual SQL or `tools/*.php` on staging/production)  
3. `graphify update .` only after **structural** PHP changes (controllers/models/services) — not every tweak (Cursor speed)

## Graphify (optional map)

- **Speed policy:** domain docs first; graphify only for cross-module architecture questions.
- **Ignored:** `wf/`, `wh2/`, and PWA/icon assets (`.graphifyignore`) — incomplete forks / noise.
- Rebuild rarely: `/graphify .` after large architecture or domain-doc rewrites; day-to-day use `graphify update .` when structure changes.

## Bug fixes

Add a dated note under **History** in the relevant file:

```markdown
### 2026-06-11 — Short title
Symptom → root cause → fix (file/method).
```
