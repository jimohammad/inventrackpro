<?php

require_once __DIR__ . '/BaseController.php';

/**
 * Mandoob Inventory — reminders for physical van stock counts (default every 3 months).
 */
class MandoobInventoryController extends BaseController {

    private Database $db;

    /** @var bool|null null = unchecked, true = ready, false = failed */
    private static ?bool $schemaReady = null;

    public function __construct() {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->ensureSchema();
    }

    private function schemaIsReady(): bool {
        return self::$schemaReady === true;
    }

    private function schemaFailureRedirect(): void {
        $this->flash(
            'error',
            'Mandoob Inventory could not update the database schema automatically. Apply the mandoob tables from database/schema.sql on the server, then try again.'
        );
        $this->redirect('?page=mandoob_inventory');
    }

    /**
     * Add pause/history columns if migration was not run on deployed DB.
     */
    private function ensureSchema(): void {
        if (self::$schemaReady === true) {
            return;
        }
        if (self::$schemaReady === false) {
            return;
        }

        try {
            $pausedCol = $this->db->fetchOne(
                "SELECT 1 AS ok FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'mandoob_inventory_schedules'
                   AND COLUMN_NAME = 'is_paused'
                 LIMIT 1"
            );
            if (!$pausedCol) {
                $this->db->execute(
                    "ALTER TABLE mandoob_inventory_schedules
                     ADD COLUMN is_paused TINYINT(1) NOT NULL DEFAULT 0 AFTER next_due_date"
                );
            }

            $pausedAtCol = $this->db->fetchOne(
                "SELECT 1 AS ok FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'mandoob_inventory_schedules'
                   AND COLUMN_NAME = 'paused_at'
                 LIMIT 1"
            );
            if (!$pausedAtCol) {
                $this->db->execute(
                    "ALTER TABLE mandoob_inventory_schedules
                     ADD COLUMN paused_at DATE DEFAULT NULL AFTER is_paused"
                );
            }

            $histTbl = $this->db->fetchOne(
                "SELECT 1 AS ok FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'mandoob_inventory_history'
                 LIMIT 1"
            );
            if (!$histTbl) {
                $this->db->execute(
                    "CREATE TABLE mandoob_inventory_history (
                        id              INT AUTO_INCREMENT PRIMARY KEY,
                        schedule_id     INT NOT NULL,
                        warehouse_id    INT NOT NULL,
                        count_date      DATE NOT NULL,
                        next_due_after  DATE DEFAULT NULL,
                        notes           VARCHAR(500) DEFAULT NULL,
                        line_count      INT NOT NULL DEFAULT 0,
                        total_qty       INT NOT NULL DEFAULT 0,
                        recorded_by     INT DEFAULT NULL,
                        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (schedule_id) REFERENCES mandoob_inventory_schedules(id) ON DELETE CASCADE,
                        FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
                        FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
                        INDEX idx_mandoob_hist_schedule (schedule_id, count_date DESC),
                        INDEX idx_mandoob_hist_wh (warehouse_id, count_date DESC)
                    )"
                );
            } else {
                $this->ensureHistoryCountColumns();
            }

            $this->ensureCountItemsTable();

            self::$schemaReady = true;
        } catch (Throwable $e) {
            error_log('[MandoobInventory] ensureSchema failed: ' . $e->getMessage());
            self::$schemaReady = false;
        }
    }

    private function ensureHistoryCountColumns(): void {
        foreach (['line_count', 'total_qty'] as $col) {
            $exists = $this->db->fetchOne(
                "SELECT 1 AS ok FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'mandoob_inventory_history'
                   AND COLUMN_NAME = ?
                 LIMIT 1",
                [$col]
            );
            if (!$exists) {
                if ($col !== 'line_count' && $col !== 'total_qty') {
                    continue;
                }
                $this->db->execute(
                    "ALTER TABLE mandoob_inventory_history
                     ADD COLUMN {$col} INT NOT NULL DEFAULT 0"
                );
            }
        }
    }

    private function ensureCountItemsTable(): void {
        $tbl = $this->db->fetchOne(
            "SELECT 1 AS ok FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'mandoob_inventory_count_items'
             LIMIT 1"
        );
        if ($tbl) {
            return;
        }
        $this->db->execute(
            "CREATE TABLE mandoob_inventory_count_items (
                id              INT AUTO_INCREMENT PRIMARY KEY,
                history_id      INT NOT NULL,
                warehouse_id    INT NOT NULL,
                item_id         INT NOT NULL,
                item_name       VARCHAR(200) NOT NULL,
                sku             VARCHAR(80) DEFAULT NULL,
                quantity        INT NOT NULL DEFAULT 1,
                imeis           TEXT DEFAULT NULL,
                created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_mi_ci_hist FOREIGN KEY (history_id) REFERENCES mandoob_inventory_history(id) ON DELETE CASCADE,
                CONSTRAINT fk_mi_ci_wh FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
                CONSTRAINT fk_mi_ci_item FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE RESTRICT,
                INDEX idx_mi_ci_hist (history_id),
                INDEX idx_mi_ci_wh (warehouse_id, history_id)
            )"
        );
    }

    private function whId(): int {
        return (int) Auth::warehouseId();
    }

    /** Clamp interval to 1–24 months. */
    private function intervalMonths(): int {
        $m = $this->inputInt('interval_months', 3);
        return max(1, min(24, $m > 0 ? $m : 3));
    }

    /** Parse Y-m-d or empty → null. */
    private function parseDate(string $key, string $from = 'post'): ?string {
        $raw = trim($this->input($key, '', $from, 20));
        if ($raw === '') {
            return null;
        }
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $raw);
        return $dt ? $dt->format('Y-m-d') : null;
    }

    private function addMonthsTo(?string $ymd, int $months): ?string {
        if ($ymd === null || $ymd === '') {
            return null;
        }
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $ymd);
        if (!$dt) {
            return null;
        }
        return $dt->modify('+' . max(1, $months) . ' months')->format('Y-m-d');
    }

    /** Primary + secondary phone for storage (max 40 chars). */
    private function phoneFromParty(array $party): ?string {
        $a = trim((string) ($party['phone'] ?? ''));
        $b = trim((string) ($party['phone2'] ?? ''));
        $s = ($a !== '' && $b !== '') ? ($a . ' / ' . $b) : ($a !== '' ? $a : $b);
        if ($s === '') {
            return null;
        }
        if (function_exists('mb_substr')) {
            return mb_substr($s, 0, 40, 'UTF-8');
        }
        return substr($s, 0, 40);
    }

    /** True if no other active row uses this party in this warehouse. */
    private function isPartySlotFree(int $partyId, int $excludeScheduleId = 0): bool {
        $params = [$this->whId(), $partyId];
        $sql    = "SELECT id FROM mandoob_inventory_schedules
                   WHERE warehouse_id = ? AND party_id = ? AND is_active = 1";
        if ($excludeScheduleId > 0) {
            $sql .= " AND id != ?";
            $params[] = $excludeScheduleId;
        }
        $row = $this->db->fetchOne($sql, $params);
        return !$row;
    }

    /** @return array<int, array{item_id:int,quantity:int,imeis:?string}> */
    private function parseCountItems(): array {
        $raw = $_POST['items'] ?? [];
        if (!is_array($raw)) {
            return [];
        }

        $merged = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $itemId = (int) ($row['item_id'] ?? 0);
            $qty    = (int) ($row['quantity'] ?? 0);
            if ($itemId <= 0 || $qty < 1) {
                continue;
            }
            if ($qty > 9999) {
                $qty = 9999;
            }
            $imeiRaw = (string) ($row['imeis'] ?? '');
            if (strlen($imeiRaw) > 8000) {
                $imeiRaw = substr($imeiRaw, 0, 8000);
            }
            $imeis = $this->normalizeImeiList($imeiRaw);
            if (isset($merged[$itemId])) {
                $merged[$itemId]['quantity'] += $qty;
                if ($merged[$itemId]['quantity'] > 9999) {
                    $merged[$itemId]['quantity'] = 9999;
                }
                if ($imeis !== null) {
                    $prev = (string) ($merged[$itemId]['imeis'] ?? '');
                    $merged[$itemId]['imeis'] = $this->normalizeImeiList($prev . "\n" . $imeis);
                }
            } else {
                $merged[$itemId] = [
                    'item_id'  => $itemId,
                    'quantity' => $qty,
                    'imeis'    => $imeis,
                ];
            }
            if (count($merged) >= 200) {
                break;
            }
        }

        return array_values($merged);
    }

    private function normalizeImeiList(string $raw): ?string {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $parts = preg_split('/[\s,;]+/', $raw) ?: [];
        $out   = [];
        $seen  = [];
        foreach ($parts as $part) {
            $token = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) $part) ?? '');
            $len   = strlen($token);
            if ($len < 8 || $len > 20 || isset($seen[$token])) {
                continue;
            }
            $seen[$token] = true;
            $out[] = $token;
        }
        return $out !== [] ? implode("\n", $out) : null;
    }

    /**
     * @param list<array{item_id:int,quantity:int,imeis:?string}> $parsed
     * @return list<array{item_id:int,item_name:string,sku:?string,quantity:int,imeis:?string}>
     */
    private function hydrateCountItems(array $parsed): array {
        if ($parsed === []) {
            return [];
        }
        $ids = [];
        foreach ($parsed as $row) {
            $ids[] = (int) $row['item_id'];
        }
        $ids = array_values(array_unique($ids));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = $this->db->fetchAll(
            "SELECT id, name, sku FROM items WHERE id IN ({$placeholders})",
            $ids
        );
        $byId = [];
        foreach ($rows as $row) {
            $byId[(int) $row['id']] = $row;
        }

        $out = [];
        foreach ($parsed as $row) {
            $item = $byId[$row['item_id']] ?? null;
            if (!$item) {
                continue;
            }
            $out[] = [
                'item_id'   => (int) $item['id'],
                'item_name' => (string) $item['name'],
                'sku'       => $item['sku'] !== null && $item['sku'] !== '' ? (string) $item['sku'] : null,
                'quantity'  => (int) $row['quantity'],
                'imeis'     => $row['imeis'],
            ];
        }
        return $out;
    }

    public function index(): void {
        Auth::authorize('mandoob_inventory', 'view');
        $whId = $this->whId();

        $rows = $this->db->fetchAll(
            "SELECT m.*, u.name AS created_by_name
             FROM mandoob_inventory_schedules m
             LEFT JOIN users u ON u.id = m.created_by
             WHERE m.warehouse_id = ? AND m.is_active = 1
             ORDER BY
                 CASE WHEN m.next_due_date IS NULL THEN 1 ELSE 0 END ASC,
                 m.next_due_date ASC,
                 m.name ASC",
            [$whId]
        );

        $today = date('Y-m-d');
        $pageTitle = 'Mandoob Inventory';
        $page      = 'mandoob_inventory';

        ob_start();
        include __DIR__ . '/../views/mandoob_inventory/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function create(): void {
        Auth::authorize('mandoob_inventory', 'add');
        $partyModel = new Party();
        $customers = $partyModel->getCustomersForSelect();

        $pageTitle = 'Add Mandoob';
        $page      = 'mandoob_inventory';
        $row       = null;

        ob_start();
        include __DIR__ . '/../views/mandoob_inventory/form.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function edit(): void {
        Auth::authorize('mandoob_inventory', 'edit');
        $id   = $this->inputInt('id', 0, 'get');
        $whId = $this->whId();
        $row  = $this->db->fetchOne(
            "SELECT * FROM mandoob_inventory_schedules WHERE id = ? AND warehouse_id = ? AND is_active = 1",
            [$id, $whId]
        );
        if (!$row) {
            $this->flash('error', 'Record not found.');
            $this->redirect('?page=mandoob_inventory');
        }

        $partyModel = new Party();
        $customers = $partyModel->getCustomersForSelect();

        $pageTitle = 'Edit Mandoob';
        $page      = 'mandoob_inventory';

        ob_start();
        include __DIR__ . '/../views/mandoob_inventory/form.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function store(): void {
        Auth::authorize('mandoob_inventory', 'add');
        if (!$this->isPost()) {
            $this->redirect('?page=mandoob_inventory');
        }

        $partyModel = new Party();
        $partyId    = $this->inputInt('party_id');
        if ($partyId <= 0) {
            $this->flash('error', 'Please select a customer.');
            $this->redirect('?page=mandoob_inventory&action=create');
        }

        $party = $partyModel->getForMandoobSchedule($partyId);
        if (!$party) {
            $this->flash('error', 'Invalid customer or not available in this warehouse.');
            $this->redirect('?page=mandoob_inventory&action=create');
        }

        if (!$this->isPartySlotFree($partyId)) {
            $this->flash('error', 'This customer is already on the mandoob list.');
            $this->redirect('?page=mandoob_inventory&action=create');
        }

        $name  = $party['name'];
        $phone = $this->phoneFromParty($party);

        $months    = $this->intervalMonths();
        $last      = $this->parseDate('last_count_date');
        $nextInput = $this->parseDate('next_due_date');
        $next      = $nextInput;
        if ($last !== null && $nextInput === null) {
            $next = $this->addMonthsTo($last, $months);
        }

        $this->db->insert(
            "INSERT INTO mandoob_inventory_schedules
                (warehouse_id, party_id, name, phone, interval_months, last_count_date, next_due_date, notes, created_by)
             VALUES (?,?,?,?,?,?,?,?,?)",
            [
                $this->whId(),
                $partyId,
                $name,
                $phone,
                $months,
                $last,
                $next,
                $this->input('notes', '', 'post', 2000) ?: null,
                Auth::id(),
            ]
        );

        $this->flash('success', 'Mandoob added.');
        $this->redirect('?page=mandoob_inventory');
    }

    public function update(): void {
        Auth::authorize('mandoob_inventory', 'edit');
        if (!$this->isPost()) {
            $this->redirect('?page=mandoob_inventory');
        }

        $id   = $this->inputInt('id');
        $whId = $this->whId();
        $exists = $this->db->fetchOne(
            "SELECT id FROM mandoob_inventory_schedules WHERE id = ? AND warehouse_id = ? AND is_active = 1",
            [$id, $whId]
        );
        if (!$exists) {
            $this->flash('error', 'Record not found.');
            $this->redirect('?page=mandoob_inventory');
        }

        $partyModel = new Party();
        $partyId    = $this->inputInt('party_id');
        if ($partyId <= 0) {
            $this->flash('error', 'Please select a customer.');
            $this->redirect('?page=mandoob_inventory&action=edit&id=' . $id);
        }

        $party = $partyModel->getForMandoobSchedule($partyId);
        if (!$party) {
            $this->flash('error', 'Invalid customer or not available in this warehouse.');
            $this->redirect('?page=mandoob_inventory&action=edit&id=' . $id);
        }

        if (!$this->isPartySlotFree($partyId, $id)) {
            $this->flash('error', 'This customer is already on the mandoob list.');
            $this->redirect('?page=mandoob_inventory&action=edit&id=' . $id);
        }

        $name  = $party['name'];
        $phone = $this->phoneFromParty($party);

        $months    = $this->intervalMonths();
        $last      = $this->parseDate('last_count_date');
        $nextInput = $this->parseDate('next_due_date');
        $next      = $nextInput;
        if ($last !== null && $nextInput === null) {
            $next = $this->addMonthsTo($last, $months);
        }

        $this->db->execute(
            "UPDATE mandoob_inventory_schedules SET
                party_id = ?, name = ?, phone = ?, interval_months = ?, last_count_date = ?, next_due_date = ?, notes = ?
             WHERE id = ? AND warehouse_id = ?",
            [
                $partyId,
                $name,
                $phone,
                $months,
                $last,
                $next,
                $this->input('notes', '', 'post', 2000) ?: null,
                $id,
                $whId,
            ]
        );

        $this->flash('success', 'Mandoob updated.');
        $this->redirect('?page=mandoob_inventory');
    }

    public function delete(): void {
        Auth::authorize('mandoob_inventory', 'delete');
        if (!$this->isPost()) {
            $this->redirect('?page=mandoob_inventory');
        }

        $id   = $this->inputInt('id');
        $whId = $this->whId();
        $this->db->execute(
            "UPDATE mandoob_inventory_schedules SET is_active = 0 WHERE id = ? AND warehouse_id = ?",
            [$id, $whId]
        );

        $this->flash('success', 'Mandoob removed from list.');
        $this->redirect('?page=mandoob_inventory');
    }

    /** Reset last-count / next-due date (inventory already done). */
    public function reset(): void {
        Auth::authorize('mandoob_inventory', 'edit');
        if (!$this->schemaIsReady()) {
            $this->schemaFailureRedirect();
        }

        $id   = $this->inputInt('id', 0, 'get');
        $whId = $this->whId();
        $row  = $this->db->fetchOne(
            "SELECT * FROM mandoob_inventory_schedules WHERE id = ? AND warehouse_id = ? AND is_active = 1",
            [$id, $whId]
        );
        if (!$row) {
            $this->flash('error', 'Record not found.');
            $this->redirect('?page=mandoob_inventory');
        }

        $today     = date('Y-m-d');
        $pageTitle = 'Reset inventory date — ' . ($row['name'] ?? '');
        $page      = 'mandoob_inventory';

        ob_start();
        include __DIR__ . '/../views/mandoob_inventory/reset.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /** Record inventory page — date, notes, and counted items. */
    public function record(): void {
        Auth::authorize('mandoob_inventory', 'edit');
        if (!$this->schemaIsReady()) {
            $this->schemaFailureRedirect();
        }

        $id   = $this->inputInt('id', 0, 'get');
        $whId = $this->whId();
        $row  = $this->db->fetchOne(
            "SELECT * FROM mandoob_inventory_schedules WHERE id = ? AND warehouse_id = ? AND is_active = 1",
            [$id, $whId]
        );
        if (!$row) {
            $this->flash('error', 'Record not found.');
            $this->redirect('?page=mandoob_inventory');
        }

        $lastItems = [];
        try {
            $lastHist = $this->db->fetchOne(
                "SELECT id FROM mandoob_inventory_history
                 WHERE schedule_id = ? AND warehouse_id = ?
                 ORDER BY count_date DESC, id DESC
                 LIMIT 1",
                [$id, $whId]
            );
            if ($lastHist) {
                $lastItems = $this->db->fetchAll(
                    "SELECT item_id, item_name, sku, quantity, imeis
                     FROM mandoob_inventory_count_items
                     WHERE history_id = ? AND warehouse_id = ?
                     ORDER BY id ASC",
                    [(int) $lastHist['id'], $whId]
                );
            }
        } catch (Throwable $e) {
            $lastItems = [];
        }

        $today     = date('Y-m-d');
        $pageTitle = 'Record inventory — ' . ($row['name'] ?? '');
        $page      = 'mandoob_inventory';

        ob_start();
        include __DIR__ . '/../views/mandoob_inventory/record.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    /** AJAX item search for the record form. */
    public function searchItems(): void {
        header('Content-Type: application/json');
        if (!Auth::can('mandoob_inventory', 'edit')) {
            http_response_code(403);
            echo json_encode([]);
            exit;
        }
        $q = $this->inputSearch('q', '', 'get');
        if (strlen($q) < 1) {
            echo json_encode([]);
            exit;
        }
        $items = (new Item())->search($q, $this->whId() ?: null);
        echo json_encode($items);
        exit;
    }

    /** Mark physical count done: save counted items and restart countdown. */
    public function record_count(): void {
        Auth::authorize('mandoob_inventory', 'edit');
        if (!$this->schemaIsReady()) {
            $this->schemaFailureRedirect();
        }
        if (!$this->isPost()) {
            $this->redirect('?page=mandoob_inventory');
        }

        $id   = $this->inputInt('id');
        $whId = $this->whId();
        $row  = $this->db->fetchOne(
            "SELECT id, interval_months, party_id, name FROM mandoob_inventory_schedules WHERE id = ? AND warehouse_id = ? AND is_active = 1",
            [$id, $whId]
        );
        if (!$row) {
            $this->flash('error', 'Record not found.');
            $this->redirect('?page=mandoob_inventory');
        }

        $countDate = $this->parseDate('count_date');
        if ($countDate === null) {
            $this->flash('error', 'Please enter a valid inventory date.');
            $returnTo = $this->input('return_to', '', 'post', 20);
            $this->redirect('?page=mandoob_inventory&action=' . ($returnTo === 'list' ? 'reset' : 'record') . '&id=' . $id);
        }

        $months = max(1, min(24, (int) ($row['interval_months'] ?? 3)));
        $next   = $this->addMonthsTo($countDate, $months);
        $notes  = trim($this->input('notes', '', 'post', 500));
        $lines  = $this->hydrateCountItems($this->parseCountItems());
        $lineCount = count($lines);
        $totalQty  = 0;
        foreach ($lines as $line) {
            $totalQty += (int) $line['quantity'];
        }

        $partyId = (int) ($row['party_id'] ?? 0);
        $sql     = 'UPDATE mandoob_inventory_schedules SET last_count_date = ?, next_due_date = ?, is_paused = 0, paused_at = NULL';
        $params  = [$countDate, $next];

        if ($partyId > 0) {
            $party = (new Party())->getForMandoobSchedule($partyId);
            if ($party) {
                $sql .= ', name = ?, phone = ?';
                $params[] = $party['name'];
                $params[] = $this->phoneFromParty($party);
            }
        }

        $sql .= ' WHERE id = ? AND warehouse_id = ?';
        $params[] = $id;
        $params[] = $whId;

        $this->db->beginTransaction();
        try {
            $this->db->execute($sql, $params);
            $historyId = $this->db->insert(
                "INSERT INTO mandoob_inventory_history
                    (schedule_id, warehouse_id, count_date, next_due_after, notes, line_count, total_qty, recorded_by)
                 VALUES (?,?,?,?,?,?,?,?)",
                [
                    $id,
                    $whId,
                    $countDate,
                    $next,
                    $notes !== '' ? $notes : null,
                    $lineCount,
                    $totalQty,
                    Auth::id(),
                ]
            );
            if ($historyId === false || (int) $historyId <= 0) {
                throw new RuntimeException('History insert failed');
            }
            foreach ($lines as $line) {
                $this->db->insert(
                    "INSERT INTO mandoob_inventory_count_items
                        (history_id, warehouse_id, item_id, item_name, sku, quantity, imeis)
                     VALUES (?,?,?,?,?,?,?)",
                    [
                        (int) $historyId,
                        $whId,
                        $line['item_id'],
                        $line['item_name'],
                        $line['sku'],
                        $line['quantity'],
                        $line['imeis'],
                    ]
                );
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            error_log('[MandoobInventory] record_count failed: ' . $e->getMessage());
            $this->flash('error', 'Could not record inventory. Please try again.');
            $returnTo = $this->input('return_to', '', 'post', 20);
            $this->redirect('?page=mandoob_inventory&action=' . ($returnTo === 'list' ? 'reset' : 'record') . '&id=' . $id);
        }

        $who = trim((string) ($row['name'] ?? 'mandoob'));
        $msg = 'Inventory recorded for ' . $who . ' on ' . $countDate . '.';
        if ($lineCount > 0) {
            $msg .= ' ' . $lineCount . ' item' . ($lineCount === 1 ? '' : 's') . ', qty ' . $totalQty . '.';
        }
        $msg .= ' Next due: ' . ($next ?? '') . '.';
        $this->logActivity('mandoob_inventory_count', 'mandoob_inventory', $id, $msg);
        $this->flash('success', $msg);
        $returnTo = $this->input('return_to', '', 'post', 20);
        if ($returnTo === 'list' || $lineCount === 0) {
            $this->redirect('?page=mandoob_inventory');
        }
        $this->redirect('?page=mandoob_inventory&action=history&id=' . $id);
    }

    /** Pause countdown (e.g. vacation) — due date frozen until resumed. */
    public function pause(): void {
        Auth::authorize('mandoob_inventory', 'edit');
        if (!$this->schemaIsReady()) {
            $this->schemaFailureRedirect();
        }
        if (!$this->isPost()) {
            $this->redirect('?page=mandoob_inventory');
        }

        $id   = $this->inputInt('id');
        $whId = $this->whId();
        $row  = $this->db->fetchOne(
            "SELECT id, is_paused FROM mandoob_inventory_schedules WHERE id = ? AND warehouse_id = ? AND is_active = 1",
            [$id, $whId]
        );
        if (!$row) {
            $this->flash('error', 'Record not found.');
            $this->redirect('?page=mandoob_inventory');
        }
        if ((int) ($row['is_paused'] ?? 0) === 1) {
            $this->flash('info', 'Inventory is already paused.');
            $this->redirect('?page=mandoob_inventory');
        }

        $today = date('Y-m-d');
        try {
            $this->db->execute(
                "UPDATE mandoob_inventory_schedules SET is_paused = 1, paused_at = ? WHERE id = ? AND warehouse_id = ?",
                [$today, $id, $whId]
            );
        } catch (Throwable $e) {
            error_log('[MandoobInventory] pause failed: ' . $e->getMessage());
            $this->flash('error', 'Could not pause inventory. Please refresh and try again.');
            $this->redirect('?page=mandoob_inventory');
        }

        $this->flash('success', 'Inventory paused — countdown frozen until you press Start Inventory.');
        $this->redirect('?page=mandoob_inventory');
    }

    /** Resume paused countdown — extends next due by days spent paused. */
    public function resume(): void {
        Auth::authorize('mandoob_inventory', 'edit');
        if (!$this->schemaIsReady()) {
            $this->schemaFailureRedirect();
        }
        if (!$this->isPost()) {
            $this->redirect('?page=mandoob_inventory');
        }

        $id   = $this->inputInt('id');
        $whId = $this->whId();
        $row  = $this->db->fetchOne(
            "SELECT id, is_paused, paused_at, next_due_date FROM mandoob_inventory_schedules WHERE id = ? AND warehouse_id = ? AND is_active = 1",
            [$id, $whId]
        );
        if (!$row) {
            $this->flash('error', 'Record not found.');
            $this->redirect('?page=mandoob_inventory');
        }
        if ((int) ($row['is_paused'] ?? 0) !== 1) {
            $this->flash('info', 'Inventory is not paused.');
            $this->redirect('?page=mandoob_inventory');
        }

        $today    = date('Y-m-d');
        $pausedAt = $row['paused_at'] ?? null;
        $nextDue  = $row['next_due_date'] ?? null;

        if ($pausedAt !== null && $pausedAt !== '' && $nextDue !== null && $nextDue !== '') {
            $tsPause = strtotime((string) $pausedAt . ' 12:00:00');
            $tsToday = strtotime($today . ' 12:00:00');
            if ($tsPause !== false && $tsToday !== false && $tsToday >= $tsPause) {
                $daysPaused = (int) floor(($tsToday - $tsPause) / 86400);
                if ($daysPaused > 0) {
                    $dt = DateTimeImmutable::createFromFormat('Y-m-d', (string) $nextDue);
                    if ($dt) {
                        $nextDue = $dt->modify('+' . $daysPaused . ' days')->format('Y-m-d');
                    }
                }
            }
        }

        try {
            $this->db->execute(
                "UPDATE mandoob_inventory_schedules SET is_paused = 0, paused_at = NULL, next_due_date = ? WHERE id = ? AND warehouse_id = ?",
                [$nextDue, $id, $whId]
            );
        } catch (Throwable $e) {
            error_log('[MandoobInventory] resume failed: ' . $e->getMessage());
            $this->flash('error', 'Could not resume inventory. Please refresh and try again.');
            $this->redirect('?page=mandoob_inventory');
        }

        $msg = 'Inventory resumed — countdown active again.';
        if ($nextDue !== null && $nextDue !== '') {
            $msg .= ' Next due: ' . $nextDue . '.';
        }
        $this->flash('success', $msg);
        $this->redirect('?page=mandoob_inventory');
    }

    /** Inventory count history for one mandoob. */
    public function history(): void {
        Auth::authorize('mandoob_inventory', 'view');
        $id   = $this->inputInt('id', 0, 'get');
        $whId = $this->whId();

        $schedule = $this->db->fetchOne(
            "SELECT * FROM mandoob_inventory_schedules WHERE id = ? AND warehouse_id = ? AND is_active = 1",
            [$id, $whId]
        );
        if (!$schedule) {
            $this->flash('error', 'Record not found.');
            $this->redirect('?page=mandoob_inventory');
        }

        $history = [];
        $itemsByHistory = [];
        try {
            $history = $this->db->fetchAll(
                "SELECT h.*, u.name AS recorded_by_name
                 FROM mandoob_inventory_history h
                 LEFT JOIN users u ON u.id = h.recorded_by
                 WHERE h.schedule_id = ? AND h.warehouse_id = ?
                 ORDER BY h.count_date DESC, h.id DESC",
                [$id, $whId]
            );
            $histIds = [];
            foreach ($history as $h) {
                $histIds[] = (int) $h['id'];
            }
            if ($histIds !== []) {
                $placeholders = implode(',', array_fill(0, count($histIds), '?'));
                $itemRows = $this->db->fetchAll(
                    "SELECT history_id, item_id, item_name, sku, quantity, imeis
                     FROM mandoob_inventory_count_items
                     WHERE warehouse_id = ? AND history_id IN ({$placeholders})
                     ORDER BY id ASC",
                    array_merge([$whId], $histIds)
                );
                foreach ($itemRows as $itemRow) {
                    $hid = (int) $itemRow['history_id'];
                    $itemsByHistory[$hid][] = $itemRow;
                }
            }
        } catch (Throwable $e) {
            $history = $history ?: [];
            $itemsByHistory = [];
        }

        $pageTitle = 'Inventory History — ' . ($schedule['name'] ?? '');
        $page      = 'mandoob_inventory';

        ob_start();
        include __DIR__ . '/../views/mandoob_inventory/history.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }
}
