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
            3 => ['label' => 'Replaced',   'icon' => 'bi-arrow-repeat', 'color' => '#8b5cf6'],
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
                SUM(status = 'Replaced') as replaced
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

            $this->logActivity('create_service', 'service', $id, "Service {$serviceNo} for {$imei}");
            $this->flash('success', "Service {$serviceNo} created.");
            $this->redirect('?page=service&action=detail&id=' . $id);
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
        $token = trim($this->input('token', '', 'get'));
        $record = null;
        $history = [];

        // Basic rate-limiting: max 30 lookups per IP per 5 minutes (protects against token brute-force)
        if ($token) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $cacheDir = sys_get_temp_dir() . '/svc_track_rl';
            if (!is_dir($cacheDir)) @mkdir($cacheDir, 0700, true);
            $rlFile = $cacheDir . '/' . md5($ip);
            $now = time();
            $hits = file_exists($rlFile) ? (array)@json_decode(file_get_contents($rlFile), true) : [];
            $hits = array_filter($hits, fn($t) => $t > $now - 300);
            if (count($hits) >= 30) {
                http_response_code(429);
                include __DIR__ . '/../views/public/service_track.php';
                return;
            }
            $hits[] = $now;
            @file_put_contents($rlFile, json_encode(array_values($hits)));

            // Token must be at least 16 chars (reject obvious brute-force attempts)
            if (strlen($token) < 16) {
                include __DIR__ . '/../views/public/service_track.php';
                return;
            }

            $record = $this->db->fetchOne(
                "SELECT id, service_no, imei, customer_name, device_brand, device_model, fault_description,
                        status, device_stage, received_date, delivered_date
                 FROM service_records WHERE tracking_token = ?",
                [$token]
            );
            if ($record) {
                $history = $this->db->fetchAll(
                    "SELECT event_type, new_value, note, created_at FROM service_history WHERE service_id = ? AND event_type IN ('created','stage_change') ORDER BY id ASC",
                    [(int)$record['id']]
                );
            }
        }

        include __DIR__ . '/../views/public/service_track.php';
    }
}
