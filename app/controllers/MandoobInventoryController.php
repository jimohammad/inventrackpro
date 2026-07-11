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
                        recorded_by     INT DEFAULT NULL,
                        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (schedule_id) REFERENCES mandoob_inventory_schedules(id) ON DELETE CASCADE,
                        FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
                        FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
                        INDEX idx_mandoob_hist_schedule (schedule_id, count_date DESC),
                        INDEX idx_mandoob_hist_wh (warehouse_id, count_date DESC)
                    )"
                );
            }

            self::$schemaReady = true;
        } catch (Throwable $e) {
            error_log('[MandoobInventory] ensureSchema failed: ' . $e->getMessage());
            self::$schemaReady = false;
        }
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

    /** Mark physical count done: restart countdown (next due = count date + interval). */
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
            "SELECT id, interval_months, party_id FROM mandoob_inventory_schedules WHERE id = ? AND warehouse_id = ? AND is_active = 1",
            [$id, $whId]
        );
        if (!$row) {
            $this->flash('error', 'Record not found.');
            $this->redirect('?page=mandoob_inventory');
        }

        $countDate = $this->parseDate('count_date');
        if ($countDate === null) {
            $this->flash('error', 'Please enter a valid inventory date.');
            $this->redirect('?page=mandoob_inventory');
        }

        $months = max(1, min(24, (int) ($row['interval_months'] ?? 3)));
        $next   = $this->addMonthsTo($countDate, $months);
        $notes  = trim($this->input('notes', '', 'post', 500));

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
            $this->db->insert(
                "INSERT INTO mandoob_inventory_history
                    (schedule_id, warehouse_id, count_date, next_due_after, notes, recorded_by)
                 VALUES (?,?,?,?,?,?)",
                [
                    $id,
                    $whId,
                    $countDate,
                    $next,
                    $notes !== '' ? $notes : null,
                    Auth::id(),
                ]
            );
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            $this->flash('error', 'Could not record inventory. Please try again.');
            $this->redirect('?page=mandoob_inventory');
        }

        $this->flash('success', 'Inventory recorded for ' . $countDate . '. Countdown reset — next due: ' . ($next ?? '') . ' (every ' . $months . ' mo).');
        $this->redirect('?page=mandoob_inventory');
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
        try {
            $history = $this->db->fetchAll(
                "SELECT h.*, u.name AS recorded_by_name
                 FROM mandoob_inventory_history h
                 LEFT JOIN users u ON u.id = h.recorded_by
                 WHERE h.schedule_id = ? AND h.warehouse_id = ?
                 ORDER BY h.count_date DESC, h.id DESC",
                [$id, $whId]
            );
        } catch (Throwable $e) {
            $history = [];
        }

        $pageTitle = 'Inventory History — ' . ($schedule['name'] ?? '');
        $page      = 'mandoob_inventory';

        ob_start();
        include __DIR__ . '/../views/mandoob_inventory/history.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }
}
