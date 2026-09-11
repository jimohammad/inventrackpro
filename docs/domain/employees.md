# Employee records (HR)

Staff identity, salary, and Kuwait residence documents. This is **not** a login-user list (that stays in Settings → User Management).

## Decisions

- One row per employee on the **session branch**. Main (`warehouse_id = 1`) is the only operational branch.
- Salary is KWD. Documents (Kuwait ID, passport, work permit) are stored under `assets/uploads/employees/` and served only through a logged-in download action.
- Residence expiry is the Civil ID / residency end date. Dashboard warns when it has expired or is due within **30 days**.
- Passport expiry is stored separately (`passport_expires_on`) and highlighted on the list/view when expired or due within 30 days.
- Nationality is a dropdown: Pakistan, Indian, Bangladesh.
- First page load creates the `employees` table if missing. Existing tables get `passport_expires_on` via auto-`ALTER`.

## Rules

- Must save `warehouse_id` from `Auth::warehouseId()`.
- List / view / edit / delete / download must filter that same branch.
- Do not add an “all warehouses” employee list.
- Fahaheel (`warehouse_id = 3`) must stay at zero rows while inactive.
- Uploads: PDF / JPG / PNG / WEBP, max 5 MB each. Direct URL access to the upload folder is denied.
- Cashier and viewer have no access unless an admin grants the `employees` module.
- Do not post salary into payments, expenses, or party balances from this page.

## Code map

| Piece | Location |
|-------|----------|
| List / create / edit / view / delete / download | `EmployeeController`, `app/views/employees/` |
| Table + next number + expiry counts | `Employee` model |
| Permission | `employees` (view / add / edit / delete) |
| Auto-schema | `Employee::ensureSchema()` + `database/migrations/2026_08_23_employees.sql` |
| Nav | Sidebar **HR → Employees** |
| Dashboard | Residence expired / due-in-30-days chip |

## Verify

1. Open **Employees** → **New Employee**. Fill name, nationality (dropdown), passport + expiry, Civil ID, salary, residence expiry. Upload Kuwait ID, passport, and work permit → Save.
2. Open the record. Files open via **View** (authenticated). Salary shows in KWD.
3. Edit: replace one file, remove another, change residence date.
4. List highlights expired / due-soon residence dates.
5. Dashboard (with `employees` view) shows the residence chip when any active employee is expired or due in 30 days.

```sql
SET @inactive_wh = 3;
SELECT 'employees' AS t, COUNT(*) AS cnt FROM employees WHERE warehouse_id = @inactive_wh;
-- cnt must be 0 while Fahaheel is inactive.
```

## History

### 2026-08-23 — Nationality dropdown + passport expiry
Nationality is Pakistan / Indian / Bangladesh only. Added `passport_expires_on` (auto-`ALTER` on existing tables). List and view highlight expired / due-soon passport dates.

### 2026-08-23 — Employee records
Need a place to keep passport, Civil ID, salary, residence expiry, and scanned ID / passport / work permit. New HR page, branch-scoped, documents not publicly downloadable.
