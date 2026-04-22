<?php

/**
 * API Endpoint: Sales
 * GET  /api/?endpoint=sales           -> list
 * GET  /api/?endpoint=sales&id=5      -> single sale with items
 * POST /api/?endpoint=sales           -> create sale
 */

$db = Database::getInstance();

switch ($method) {
    case 'GET':
        if (!hasPermission($keyPermissions, 'sales', 'read')) {
            apiError(403, 'No permission to read sales.');
        }

        $id = (int) ($_GET['id'] ?? 0);

        if ($id > 0) {
            $sale = $db->fetchOne(
                "SELECT s.*, p.name as party_name, p.phone as party_phone
                 FROM sales s
                 LEFT JOIN parties p ON p.id = s.party_id
                 WHERE s.id = ?",
                [$id]
            );
            if (!$sale) apiError(404, 'Sale not found.');

            // Get sale items
            $items = $db->fetchAll(
                "SELECT si.*, i.name as item_name, i.sku,
                        GROUP_CONCAT(ir.imei SEPARATOR ', ') as imeis
                 FROM sale_items si
                 JOIN items i ON i.id = si.item_id
                 LEFT JOIN sale_item_imei sii ON sii.sale_item_id = si.id
                 LEFT JOIN imei_records ir ON ir.id = sii.imei_id
                 WHERE si.sale_id = ?
                 GROUP BY si.id",
                [$id]
            );

            $sale['items'] = $items;
            apiSuccess(['sale' => $sale]);
        }

        // List
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, (int) ($_GET['per_page'] ?? 25));
        $offset  = ($page - 1) * $perPage;
        $params  = [];
        $where   = "WHERE 1=1";

        if (!empty($_GET['party_id'])) {
            $where .= " AND s.party_id = ?";
            $params[] = (int) $_GET['party_id'];
        }
        if (!empty($_GET['from_date'])) {
            $where .= " AND s.date >= ?";
            $params[] = $_GET['from_date'];
        }
        if (!empty($_GET['to_date'])) {
            $where .= " AND s.date <= ?";
            $params[] = $_GET['to_date'];
        }
        if (!empty($_GET['status'])) {
            $where .= " AND s.status = ?";
            $params[] = $_GET['status'];
        }

        $total = $db->fetchOne("SELECT COUNT(*) as c FROM sales s {$where}", $params)['c'];
        $sales = $db->fetchAll(
            "SELECT s.id, s.invoice_no, p.name as party, s.date, s.grand_total, s.paid_amount, s.balance, s.status
             FROM sales s
             LEFT JOIN parties p ON p.id = s.party_id
             {$where}
             ORDER BY s.created_at DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );

        apiSuccess([
            'sales'     => $sales,
            'total'     => (int) $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'last_page' => (int) ceil($total / $perPage),
        ]);
        break;

    case 'POST':
        if (!hasPermission($keyPermissions, 'sales', 'write')) {
            apiError(403, 'No permission to create sales.');
        }

        $data = getInput();

        // Basic validation
        if (empty($data['party_id']))    apiError(422, 'party_id is required.');
        if (empty($data['warehouse_id'])) apiError(422, 'warehouse_id is required.');
        if (empty($data['items']))       apiError(422, 'items array is required.');

        // Validate party and warehouse exist
        if (!$db->fetchOne("SELECT id FROM parties WHERE id = ? AND is_active = 1", [$data['party_id']])) {
            apiError(422, 'Invalid party_id — party not found or inactive.');
        }
        if (!$db->fetchOne("SELECT id FROM warehouses WHERE id = ? AND is_active = 1", [$data['warehouse_id']])) {
            apiError(422, 'Invalid warehouse_id — warehouse not found or inactive.');
        }

        // Validate items
        foreach ($data['items'] as $item) {
            if ((int)($item['quantity'] ?? 0) <= 0) apiError(422, 'Item quantity must be greater than zero.');
            if ((float)($item['unit_price'] ?? 0) < 0) apiError(422, 'Item price cannot be negative.');
        }

        $db->beginTransaction();
        try {
            // BUG FIX: Added FOR UPDATE to prevent duplicate invoice numbers under concurrency.
            // Previously two simultaneous API requests could generate the same invoice_no.
            $lastSale  = $db->fetchOne("SELECT invoice_no FROM sales ORDER BY id DESC LIMIT 1 FOR UPDATE");
            $lastNum   = $lastSale ? (int) substr($lastSale['invoice_no'], strlen(SALE_PREFIX)) : 0;
            $invoiceNo = SALE_PREFIX . str_pad($lastNum + 1, 6, '0', STR_PAD_LEFT);

            $subtotal = 0;
            $discount = (float) ($data['discount'] ?? 0);

            foreach ($data['items'] as $item) {
                $subtotal += (float)$item['unit_price'] * (int)$item['quantity'];
            }

            $grandTotal = $subtotal - $discount;
            // Only accept payment if account_id is provided — prevents orphan paid_amount
            $paid       = (!empty($data['account_id']) && (float)($data['paid_amount'] ?? 0) > 0)
                          ? (float) $data['paid_amount'] : 0;
            $balance    = $grandTotal - $paid;

            // BUG FIX: Clamp negative balance to 0. Previously overpayment stored
            // a negative balance in the database.
            if ($balance < 0.001) {
                $status  = 'paid';
                $balance = 0;
            } elseif ($paid > 0) {
                $status = 'partial';
            } else {
                $status = 'confirmed';
            }

            // Insert sale
            $saleId = $db->insert(
                "INSERT INTO sales (invoice_no, party_id, warehouse_id, date, subtotal, discount, grand_total, paid_amount, balance, status, notes)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                [
                    $invoiceNo,
                    $data['party_id'],
                    $data['warehouse_id'],
                    $data['date'] ?? date('Y-m-d'),
                    $subtotal,
                    $discount,
                    $grandTotal,
                    $paid,
                    $balance,
                    $status,
                    $data['notes'] ?? null
                ]
            );

            // Insert items + update stock
            foreach ($data['items'] as $item) {
                $itemTotal = (float)$item['unit_price'] * (int)$item['quantity'];
                $saleItemId = $db->insert(
                    "INSERT INTO sale_items (sale_id, item_id, quantity, unit_price, discount, total)
                     VALUES (?,?,?,?,?,?)",
                    [$saleId, $item['item_id'], $item['quantity'], $item['unit_price'], $item['discount'] ?? 0, $itemTotal]
                );

                // BUG FIX: Atomic stock check + deduct in one query to prevent race condition.
                // Previously the check and deduct were separate queries, so two concurrent
                // requests could both pass the check and oversell.
                $affected = $db->execute(
                    "UPDATE stock SET quantity = quantity - ?
                     WHERE item_id = ? AND warehouse_id = ? AND quantity >= ?",
                    [(int)$item['quantity'], $item['item_id'], $data['warehouse_id'], (int)$item['quantity']]
                );
                if ($affected === 0) {
                    $stock = $db->fetchOne(
                        "SELECT quantity FROM stock WHERE item_id = ? AND warehouse_id = ?",
                        [$item['item_id'], $data['warehouse_id']]
                    );
                    throw new Exception(
                        "Insufficient stock for item ID {$item['item_id']}. " .
                        "Available: " . ($stock['quantity'] ?? 0) . ", Requested: {$item['quantity']}."
                    );
                }

                // Attach IMEIs if provided
                if (!empty($item['imeis'])) {
                    foreach ($item['imeis'] as $imei) {
                        $imeiRow = $db->fetchOne("SELECT id, status FROM imei_records WHERE imei = ? FOR UPDATE", [$imei]);
                        if (!$imeiRow) throw new Exception("IMEI {$imei} not found.");
                        if ($imeiRow['status'] !== 'in_stock') throw new Exception("IMEI {$imei} is not available (status: {$imeiRow['status']}).");
                        $db->insert("INSERT INTO sale_item_imei (sale_item_id, imei_id) VALUES (?,?)", [$saleItemId, $imeiRow['id']]);
                        $db->execute("UPDATE imei_records SET status = 'sold', sale_id = ? WHERE id = ?", [$saleId, $imeiRow['id']]);
                    }
                }
            }

            // Record payment if amount provided
            if ($paid > 0) {
                // BUG FIX: Added FOR UPDATE to prevent duplicate payment numbers.
                $lastPay  = $db->fetchOne("SELECT payment_no FROM payments ORDER BY id DESC LIMIT 1 FOR UPDATE");
                $payNum   = $lastPay ? (int) substr($lastPay['payment_no'], 4) : 0;
                $payNo    = 'PAY-' . str_pad($payNum + 1, 6, '0', STR_PAD_LEFT);

                $accountId = $data['account_id'] ?? 1;

                $db->insert(
                    "INSERT INTO payments (payment_no, ref_type, ref_id, party_id, payment_type, account_id, amount, payment_method, date, warehouse_id, created_by)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                    [$payNo, 'sale', $saleId, $data['party_id'], 'in', $accountId, $paid,
                     $data['payment_method'] ?? 'cash', $data['date'] ?? date('Y-m-d'),
                     $data['warehouse_id'] ?? null, null]
                );

                // AUDIT FIX F4: Update account balance (was missing — caused balance drift)
                $db->execute(
                    "UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?",
                    [$paid, $accountId]
                );
            }

            $db->commit();
            apiSuccess(['message' => 'Sale created.', 'id' => (int)$saleId, 'invoice_no' => $invoiceNo], 201);

        } catch (Exception $e) {
            $db->rollback();
            error_log("API Sale Error: " . $e->getMessage());
            apiError(500, 'Failed to create sale. Please try again.');
        }
        break;

    default:
        apiError(405, 'Method not allowed.');
}
