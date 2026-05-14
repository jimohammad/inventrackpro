<?php

require_once __DIR__ . '/BaseController.php';

/**
 * Mandoob Inventory — reminders for physical van stock counts (default every 3 months).
 */
class MandoobInventoryController extends BaseController {

    private Database $db;

    public function __construct() {
        parent::__construct();
        $this->db = Database::getInstance();
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

    /** Mark physical count done today: restart countdown (next due = today + interval). */
    public function record_count(): void {
        Auth::authorize('mandoob_inventory', 'edit');
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

        $today  = date('Y-m-d');
        $months = max(1, min(24, (int) ($row['interval_months'] ?? 3)));
        $next   = $this->addMonthsTo($today, $months);

        $partyId = (int) ($row['party_id'] ?? 0);
        $sql     = 'UPDATE mandoob_inventory_schedules SET last_count_date = ?, next_due_date = ?';
        $params  = [$today, $next];

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

        $this->db->execute($sql, $params);

        $this->flash('success', 'Inventory done — countdown restarted. Next due: ' . ($next ?? '') . ' (every ' . $months . ' mo).');
        $this->redirect('?page=mandoob_inventory');
    }
}
