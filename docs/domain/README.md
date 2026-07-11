# Iqbal ERP — Business Domain Rules

Human- and AI-readable **business brain** for money, stock, and branch logic.  
Graphify maps **code structure**; these files map **what must stay true**.

## When to read

| You are changing… | Read first |
|-------------------|------------|
| Reports, balances, statements, payments | [party-balance-payments.md](party-balance-payments.md) |
| Warehouse picker, branch reports, public links | [warehouse-isolation.md](warehouse-isolation.md) |
| Purchase orders, import shipments, landed cost | [purchase-orders-import.md](purchase-orders-import.md) |
| Sales/purchase returns, stock, IMEI | [stock-imei.md](stock-imei.md) |
| Discounts, payment `ref_type` | [discounts-ref-types.md](discounts-ref-types.md) |
| Accounts, net worth, freight payables | [accounts-landed-cost.md](accounts-landed-cost.md) |

## Template (for new rules)

Each domain file uses:

1. **Decisions** — why the business works this way  
2. **Rules** — must / must-not  
3. **Code map** — where enforced in PHP  
4. **Verify** — SQL audit or manual check  

## After code changes

1. Update the matching domain file if behavior changed  
2. Run `graphify update .` (code AST, free)  
3. Run the **Verify** checks in the matching domain file (manual SQL or `tools/*.php` on staging/production)  

## Semantic search (graphify + Gemini)

Domain docs are indexed in `graphify-out/graph.json`. Examples:

```bash
graphify query "KWD purchase order import logistics"
graphify explain "purchase_orders_import"
```

Wiki (community overview): `graphify-out/wiki/index.md`  
Re-build after large doc changes: `graphify . --wiki` then `graphify cluster-only .` and `graphify export wiki`

## Bug fixes

Add a dated note under **History** in the relevant file:

```markdown
### 2026-06-11 — Short title
Symptom → root cause → fix (file/method).
```
