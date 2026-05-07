<?php

require_once __DIR__ . '/BaseController.php';

class ServiceController extends BaseController {

    private Database $db;

    /** Stage definitions */
    public static function stages(): array {
        return [
            0 => ['label' => 'Received',   'icon' => 'bi-inbox',        'color' => '#f59e0b'],
            1 => ['label' => 'In Service', 'icon' => 'bi-tools',        'color' => '#3b82f6'],
            2 => ['label' => 'Repaired',   'icon' => 'bi-check-circle', 'color' => '#22c55e'],
            4 => ['label' => 'Delivered',  'icon' => 'bi-bag-check',    'color' => '#10b981'],
        ];
    }

    public static function statusColor(string $status): string {
        return match($status) {
            'Pending'     => '#f59e0b',
            'In Progress' => '#3b82f6',
            'Completed'   => '#22c55e',
            'Replaced'    => '#8b5cf6',
            default       => '#6b7280',
        };
    }

    /** Preset values = item categories in use (service intake create/edit). */
    public static function deviceBrandOptions(): array {
        return [
            'Buds',
            'Charger',
            'Honor',
            'Laptop',
            'Meizu',
            'Motorola',
            'Realme',
            'Redmi',
            'Samsung',
        ];
    }

    public function __construct() {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    public function index(): void {
        // If accessed via servicetrack page, route to public tracking
        if (($_GET['page'] ?? '') === 'servicetrack') {
            $this->track();
            return;
        }
        Auth::authorize('service', 'view');

        $whId  = Auth::warehouseId();
        $filters = [
            'search' => $this->input('search', '', 'get'),
            'status' => $this->input('status', '', 'get'),
            'stage'  => $this->input('stage', '', 'get'),
        ];

        $where  = 'WHERE sr.warehouse_id = ?';
        $params = [$whId];

        if ($filters['search']) {
            $where .= ' AND (sr.imei LIKE ? OR sr.customer_name LIKE ? OR sr.customer_phone LIKE ? OR sr.device_model LIKE ? OR sr.service_no LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }
        if ($filters['status'] !== '') {
            $where .= ' AND sr.status = ?';
            $params[] = $filters['status'];
        }
        if ($filters['stage'] !== '') {
            $where .= ' AND sr.device_stage = ?';
            $params[] = (int)$filters['stage'];
        }

        $records = $this->db->fetchAll(
            "SELECT sr.*, p.name as party_name
             FROM service_records sr
             LEFT JOIN parties p ON p.id = sr.party_id
             $where
             ORDER BY sr.id DESC
             LIMIT 500",
            $params
        );

        // Summary counts
        $counts = $this->db->fetchOne(
            "SELECT
                COUNT(*) as total,
                SUM(status = 'Pending') as pending,
                SUM(status = 'In Progress') as in_progress,
                SUM(status = 'Completed') as completed,
                SUM(status = 'Replaced') as replaced,
                SUM(device_stage = 4) as delivered
             FROM service_records WHERE warehouse_id = ?",
            [$whId]
        );

        $pageTitle = 'Service Center';
        $page      = 'service';

        ob_start();
        include __DIR__ . '/../views/service/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function create(): void {
        Auth::authorize('service', 'add');

        if ($this->isPost()) {
            $imei = trim($this->input('imei'));
            if (!$imei) {
                $this->flash('error', 'IMEI is required');
                $this->redirect('?page=service&action=create');
                return;
            }

            $token = bin2hex(random_bytes(24)); // 48-char token — resistant to brute force

            $this->db->beginTransaction();
            try {
                // Generate service number inside transaction (FOR UPDATE must be effective).
                $lastNo = $this->db->fetchOne("SELECT service_no FROM service_records ORDER BY id DESC LIMIT 1 FOR UPDATE");
                $num = $lastNo ? (int)substr($lastNo['service_no'], 4) : 0;
                $serviceNo = 'SRV-' . str_pad($num + 1, 6, '0', STR_PAD_LEFT);

                $id = $this->db->insert(
                    "INSERT INTO service_records (service_no, imei, party_id, customer_name, customer_phone, device_brand, device_model, warehouse_id, fault_category, fault_description, technician_name, repair_cost, tracking_token, notes, received_date, created_by)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                    [
                        $serviceNo,
                        $imei,
                        $this->inputInt('party_id') ?: null,
                        $this->input('customer_name'),
                        $this->input('customer_phone'),
                        $this->input('device_brand'),
                        $this->input('device_model'),
                        Auth::warehouseId(),
                        $this->input('fault_category'),
                        $this->input('fault_description'),
                        $this->input('technician_name'),
                        (float)$this->input('repair_cost', 0),
                        $token,
                        $this->input('notes'),
                        $this->input('received_date') ?: date('Y-m-d'),
                        Auth::id(),
                    ]
                );

                $this->db->insert(
                    "INSERT INTO service_history (service_id, event_type, new_value, note, user_id) VALUES (?,?,?,?,?)",
                    [$id, 'created', 'Pending', 'Service record created', Auth::id()]
                );

                $this->db->commit();
            } catch (Exception $e) {
                $this->db->rollback();
                $this->flash('error', 'Failed to create service record: ' . $e->getMessage());
                $this->redirect('?page=service&action=create');
                return;
            }

            $this->logActivity('create_service', 'service', $id, "Service {$serviceNo} for {$imei}");
            $this->flash('success', "Service {$serviceNo} created.");
            if ($this->input('save_action') === 'thermal') {
                $this->redirect('?page=service&action=thermalReceipt&id=' . $id . '&autoprint=1');
            } else {
                $this->redirect('?page=service&action=detail&id=' . $id);
            }
            return;
        }

        $parties = $this->db->fetchAll("SELECT id, name, phone FROM parties WHERE is_active = 1 ORDER BY name");

        $pageTitle = 'New Service';
        $page      = 'service';

        ob_start();
        include __DIR__ . '/../views/service/create.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function detail(): void {
        Auth::authorize('service', 'view');

        $id = $this->inputInt('id', 0, 'get');
        $record = $this->db->fetchOne(
            "SELECT sr.*, p.name as party_name, w.name as warehouse_name, i.name as replacement_item_name
             FROM service_records sr
             LEFT JOIN parties p ON p.id = sr.party_id
             LEFT JOIN warehouses w ON w.id = sr.warehouse_id
             LEFT JOIN items i ON i.id = sr.replacement_item_id
             WHERE sr.id = ? AND sr.warehouse_id = ?",
            [$id, Auth::warehouseId()]
        );

        if (!$record) { $this->flash('error', 'Service record not found'); $this->redirect('?page=service'); return; }

        $history = $this->db->fetchAll(
            "SELECT sh.*, u.name as user_name FROM service_history sh LEFT JOIN users u ON u.id = sh.user_id WHERE service_id = ? ORDER BY sh.id DESC",
            [$id]
        );

        $pageTitle = 'Service ' . $record['service_no'];
        $page      = 'service';

        ob_start();
        include __DIR__ . '/../views/service/detail.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * Customer-facing 80mm-style thermal receipt (standalone HTML, no app layout).
     */
    public function thermalReceipt(): void {
        Auth::authorize('service', 'view');

        $id = $this->inputInt('id', 0, 'get');
        $record = $this->db->fetchOne(
            "SELECT sr.*, p.name as party_name, w.name as warehouse_name
             FROM service_records sr
             LEFT JOIN parties p ON p.id = sr.party_id
             LEFT JOIN warehouses w ON w.id = sr.warehouse_id
             WHERE sr.id = ? AND sr.warehouse_id = ?",
            [$id, Auth::warehouseId()]
        );

        if (!$record) {
            http_response_code(404);
            echo 'Service record not found.';
            return;
        }

        $settings = self::getSettings();
        $stages   = self::stages();
        $tok      = trim((string) ($record['tracking_token'] ?? ''));
        $trackUrl = $tok !== '' ? app_service_track_url($tok) : '';

        include __DIR__ . '/../views/service/thermal_receipt.php';
    }

    public function updateStage(): void {
        Auth::authorize('service', 'edit');
        if (!$this->isPost()) { $this->redirect('?page=service'); return; }

        $id = $this->inputInt('id');
        $stage = (int)$this->input('stage');
        $note = trim($this->input('note'));

        $old = $this->db->fetchOne("SELECT device_stage, status, service_no FROM service_records WHERE id = ? AND warehouse_id = ?", [$id, Auth::warehouseId()]);
        if (!$old) { $this->redirect('?page=service'); return; }

        $newStatus = $old['status'];
        if ($stage >= 4) $newStatus = ($old['status'] === 'Replaced') ? 'Replaced' : 'Completed';
        elseif ($stage === 3) $newStatus = 'Replaced';
        elseif ($stage >= 1) $newStatus = 'In Progress';

        $delivered = $stage >= 4 ? date('Y-m-d') : null;

        $this->db->execute(
            "UPDATE service_records SET device_stage = ?, status = ?, delivered_date = ? WHERE id = ? AND warehouse_id = ?",
            [$stage, $newStatus, $delivered, $id, Auth::warehouseId()]
        );

        $stages = self::stages();
        $this->db->insert(
            "INSERT INTO service_history (service_id, event_type, old_value, new_value, note, user_id) VALUES (?,?,?,?,?,?)",
            [$id, 'stage_change', 'stage_' . $old['device_stage'], 'stage_' . $stage . ' (' . $stages[$stage]['label'] . ')', $note ?: null, Auth::id()]
        );

        $this->flash('success', "Stage updated to {$stages[$stage]['label']}");
        $this->redirect('?page=service&action=detail&id=' . $id);
    }

    /**
     * Quick status update from the main list page (AJAX-friendly).
     * Keeps device_stage loosely in sync with status to avoid conflicting state.
     */
    public function updateStatus(): void {
        Auth::authorize('service', 'edit');
        if (!$this->isPost()) { $this->redirect('?page=service'); return; }

        $id     = $this->inputInt('id');
        $status = trim($this->input('status'));

        $allowed = ['Pending', 'In Progress', 'Completed', 'Replaced'];
        if (!$id || !in_array($status, $allowed, true)) {
            $this->json(['success' => false, 'message' => 'Invalid status.'], 400);
        }

        $old = $this->db->fetchOne(
            "SELECT id, service_no, status, device_stage, delivered_date
             FROM service_records
             WHERE id = ? AND warehouse_id = ?",
            [$id, Auth::warehouseId()]
        );
        if (!$old) {
            $this->json(['success' => false, 'message' => 'Record not found.'], 404);
        }

        $newStage = (int)($old['device_stage'] ?? 0);
        $delivered = $old['delivered_date'] ?? null;

        // Map status to a reasonable stage (do not auto-deliver)
        if ($status === 'Pending') {
            $newStage = 0;
            $delivered = null;
        } elseif ($status === 'In Progress') {
            $newStage = max(1, $newStage);
            if ($newStage >= 4) $newStage = 1;
            $delivered = null;
        } elseif ($status === 'Completed' || $status === 'Replaced') {
            // Consider repaired/replaced but not delivered yet
            if ($newStage < 2 || $newStage >= 4) $newStage = 2;
            $delivered = null;
        }

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "UPDATE service_records
                 SET status = ?, device_stage = ?, delivered_date = ?
                 WHERE id = ? AND warehouse_id = ?",
                [$status, $newStage, $delivered, $id, Auth::warehouseId()]
            );

            $this->db->insert(
                "INSERT INTO service_history (service_id, event_type, old_value, new_value, note, user_id)
                 VALUES (?,?,?,?,?,?)",
                [$id, 'status_change', (string)$old['status'], $status, 'Updated from list', Auth::id()]
            );

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollback();
            $this->json(['success' => false, 'message' => 'Update failed.'], 500);
        }

        $this->json([
            'success' => true,
            'id' => $id,
            'status' => $status,
            'stage' => $newStage,
            'message' => "{$old['service_no']} updated.",
        ]);
    }

    public function edit(): void {
        Auth::authorize('service', 'edit');

        $id = $this->inputInt('id', 0, 'get');
        $record = $this->db->fetchOne(
            "SELECT * FROM service_records WHERE id = ? AND warehouse_id = ?",
            [$id, Auth::warehouseId()]
        );
        if (!$record) { $this->flash('error', 'Service record not found'); $this->redirect('?page=service'); return; }

        $parties   = $this->db->fetchAll("SELECT id, name, phone FROM parties WHERE is_active = 1 ORDER BY name");
        $pageTitle = 'Edit ' . $record['service_no'];
        $page      = 'service';

        ob_start();
        include __DIR__ . '/../views/service/edit.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function update(): void {
        Auth::authorize('service', 'edit');
        if (!$this->isPost()) { $this->redirect('?page=service'); return; }

        $id = $this->inputInt('id');
        $record = $this->db->fetchOne("SELECT * FROM service_records WHERE id = ? AND warehouse_id = ?", [$id, Auth::warehouseId()]);
        if (!$record) { $this->flash('error', 'Service record not found'); $this->redirect('?page=service'); return; }

        $this->db->execute(
            "UPDATE service_records SET
                imei = ?, device_brand = ?, device_model = ?,
                party_id = ?, customer_name = ?, customer_phone = ?,
                fault_category = ?, fault_description = ?,
                technician_name = ?, repair_cost = ?,
                received_date = ?, notes = ?
             WHERE id = ? AND warehouse_id = ?",
            [
                trim($this->input('imei')),
                $this->input('device_brand'),
                $this->input('device_model'),
                $this->inputInt('party_id') ?: null,
                $this->input('customer_name'),
                $this->input('customer_phone'),
                $this->input('fault_category'),
                $this->input('fault_description'),
                $this->input('technician_name'),
                $this->inputFloat('repair_cost'),
                $this->input('received_date') ?: date('Y-m-d'),
                $this->input('notes'),
                $id,
                Auth::warehouseId(),
            ]
        );

        $this->logActivity('update_service', 'service', $id, "Updated {$record['service_no']}");
        $this->flash('success', "Service {$record['service_no']} updated.");
        $this->redirect('?page=service&action=detail&id=' . $id);
    }

    public function delete(): void {
        Auth::authorize('service', 'delete');
        if (!$this->isPost()) { $this->redirect('?page=service'); return; }

        $id = $this->inputInt('id');
        $rec = $this->db->fetchOne("SELECT service_no FROM service_records WHERE id = ? AND warehouse_id = ?", [$id, Auth::warehouseId()]);
        if ($rec) {
            $this->db->execute("DELETE FROM service_records WHERE id = ? AND warehouse_id = ?", [$id, Auth::warehouseId()]);
            $this->flash('success', "Service {$rec['service_no']} deleted.");
        }
        $this->redirect('?page=service');
    }

    /**
     * Public tracking page — no login required
     */
    public function track(): void {
        $tokenGet = trim($this->input('token', '', 'get'));
        $imeiGet  = trim($this->input('imei', '', 'get'));
        $trackQuery = $tokenGet !== '' ? $tokenGet : $imeiGet;

        $tokenHex   = '';
        $imeiDigits = '';
        if ($imeiGet !== '') {
            $imeiDigits = preg_replace('/\D/', '', $imeiGet);
        } elseif ($tokenGet !== '') {
            $onlyDigits = preg_replace('/\D/', '', $tokenGet);
            $looksLikeImei = strlen($onlyDigits) >= 14 && strlen($onlyDigits) <= 18
                && (ctype_digit($onlyDigits) || preg_match('/^[\d\s\-]{14,22}$/', $tokenGet));
            $looksLikeToken = strlen($tokenGet) >= 16 && strlen($tokenGet) <= 64 && ctype_xdigit($tokenGet);
            if ($looksLikeImei && !$looksLikeToken) {
                $imeiDigits = $onlyDigits;
            } elseif ($looksLikeToken) {
                $tokenHex = strtolower($tokenGet);
            } else {
                $tokenHex = strtolower($tokenGet);
            }
        }

        $record  = null;
        $history = [];
        $lookupAttempt = ($tokenHex !== '' || $imeiDigits !== '');

        // Basic rate-limiting: max 30 lookups per IP per 5 minutes
        if ($lookupAttempt) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $cacheDir = sys_get_temp_dir() . '/svc_track_rl';
            if (!is_dir($cacheDir)) @mkdir($cacheDir, 0700, true);
            $rlFile = $cacheDir . '/' . md5($ip);
            $now = time();
            $hits = file_exists($rlFile) ? (array)@json_decode((string)file_get_contents($rlFile), true) : [];
            $hits = array_filter($hits, fn($t) => $t > $now - 300);
            if (count($hits) >= 30) {
                http_response_code(429);
                $token = $trackQuery;
                include __DIR__ . '/../views/public/service_track.php';
                return;
            }
            $hits[] = $now;
            @file_put_contents($rlFile, json_encode(array_values($hits)));

            if ($imeiDigits !== '' && strlen($imeiDigits) >= 14) {
                $record = $this->db->fetchOne(
                    "SELECT id, service_no, imei, customer_name, device_brand, device_model, fault_description,
                            status, device_stage, received_date, delivered_date
                     FROM service_records
                     WHERE REPLACE(REPLACE(REPLACE(imei,' ',''),'-',''),'_','') = ?
                     ORDER BY id DESC LIMIT 1",
                    [$imeiDigits]
                );
            } elseif ($tokenHex !== '' && strlen($tokenHex) >= 16) {
                $record = $this->db->fetchOne(
                    "SELECT id, service_no, imei, customer_name, device_brand, device_model, fault_description,
                            status, device_stage, received_date, delivered_date
                     FROM service_records WHERE tracking_token = ?",
                    [$tokenHex]
                );
            }

            if ($record) {
                $history = $this->db->fetchAll(
                    "SELECT event_type, new_value, note, created_at FROM service_history WHERE service_id = ? AND event_type IN ('created','stage_change') ORDER BY id ASC",
                    [(int)$record['id']]
                );
            }
        }

        $token = $trackQuery;
        include __DIR__ . '/../views/public/service_track.php';
    }
}
