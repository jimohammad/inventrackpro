<?php

require_once __DIR__ . '/BaseModel.php';

class IMEI extends BaseModel {
    protected string $table = 'imei_records';

    // Full history for one IMEI
    public function findByIMEI(string $imei): array|false {
        return $this->db->fetchOne(
            "SELECT ir.*, i.name as item_name, i.sku,
                    w.name as warehouse_name,
                    s.invoice_no as sale_invoice,
                    p.invoice_no as purchase_invoice
             FROM imei_records ir
             JOIN items i ON i.id = ir.item_id
             LEFT JOIN warehouses w ON w.id = ir.warehouse_id
             LEFT JOIN sales s ON s.id = ir.sale_id
             LEFT JOIN purchases p ON p.id = ir.purchase_id
             WHERE ir.imei = ? OR ir.imei2 = ?",
            [$imei, $imei]
        );
    }

    // Get all IMEIs with filters
    public function getAll(array $filters = []): array {
        $where  = "WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $where .= " AND ir.status = ?"; $params[] = $filters['status'];
        }
        if (!empty($filters['item_id'])) {
            $where .= " AND ir.item_id = ?"; $params[] = $filters['item_id'];
        }
        if (!empty($filters['warehouse_id'])) {
            $where .= " AND ir.warehouse_id = ?"; $params[] = $filters['warehouse_id'];
        }
        if (!empty($filters['search'])) {
            $like    = '%' . $filters['search'] . '%';
            $where  .= " AND (ir.imei LIKE ? OR i.name LIKE ?)";
            $params  = array_merge($params, [$like, $like]);
        }

        return $this->db->fetchAll(
            "SELECT ir.*, i.name as item_name, i.sku,
                    w.name as warehouse_name,
                    s.invoice_no as sale_invoice,
                    pu.invoice_no as purchase_invoice
             FROM imei_records ir
             JOIN items i ON i.id = ir.item_id
             LEFT JOIN warehouses w ON w.id = ir.warehouse_id
             LEFT JOIN sales s ON s.id = ir.sale_id
             LEFT JOIN purchases pu ON pu.id = ir.purchase_id
             {$where}
             ORDER BY ir.created_at DESC
             LIMIT 500",
            $params
        );
    }

    // Check if IMEI exists and is available (in_stock)
    public function isAvailable(string $imei): bool {
        $row = $this->db->fetchOne(
            "SELECT status FROM imei_records WHERE imei = ?", [$imei]
        );
        // If not in DB yet, it's new and available
        if (!$row) return true;
        return $row['status'] === 'in_stock';
    }

    // Validate multiple IMEIs for a sale item — single batch query
    public function validateList(array $imeis, int $itemId): array {
        $errors = [];
        $seen   = [];
        $clean  = [];

        foreach ($imeis as $imei) {
            $imei = trim($imei);
            if (!$imei) continue;
            if (in_array($imei, $seen)) {
                $errors[] = "IMEI {$imei} is duplicated in this invoice.";
                continue;
            }
            $seen[]  = $imei;
            $clean[] = $imei;
        }

        if (empty($clean)) return $errors;

        $placeholders = implode(',', array_fill(0, count($clean), '?'));
        $rows = $this->db->fetchAll(
            "SELECT imei, status, item_id FROM imei_records WHERE imei IN ({$placeholders})",
            $clean
        );
        $map = array_column($rows, null, 'imei');

        foreach ($clean as $imei) {
            $row = $map[$imei] ?? null;
            if ($row && $row['status'] === 'sold') {
                $errors[] = "IMEI {$imei} is already sold.";
            }
            if ($row && (int)$row['item_id'] !== $itemId) {
                $errors[] = "IMEI {$imei} belongs to a different product.";
            }
        }

        return $errors;
    }
}
