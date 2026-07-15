-- Payment Out as a separate permission module (same payments table / engine).
-- Run once on production. Users must log out/in (or re-save user) for session perms to refresh.
--
-- Seeds payments_out for admin/manager (and anyone with Suppliers view).
-- Cashiers / salesmen with Payment In only are NOT granted Payment Out.

INSERT INTO permissions (user_id, module, can_view, can_add, can_edit, can_delete)
SELECT p.user_id,
       'payments_out',
       p.can_view,
       p.can_add,
       p.can_edit,
       p.can_delete
FROM permissions p
INNER JOIN users u ON u.id = p.user_id
WHERE p.module = 'payments'
  AND (
      u.role IN ('admin', 'manager')
      OR EXISTS (
          SELECT 1
          FROM permissions s
          WHERE s.user_id = p.user_id
            AND s.module = 'suppliers'
            AND s.can_view = 1
      )
  )
  AND NOT EXISTS (
      SELECT 1
      FROM permissions x
      WHERE x.user_id = p.user_id
        AND x.module = 'payments_out'
  );
