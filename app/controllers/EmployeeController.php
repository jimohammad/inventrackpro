<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Employee.php';

/**
 * Employee HR records — identity, salary, Kuwait documents.
 */
class EmployeeController extends BaseController {

    private const DOC_MAX_BYTES = 5242880; // 5 MB
    private const DOC_MIME = [
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp',
    ];
    private const DOC_TYPES = [
        'kuwait_id'   => ['column' => 'kuwait_id_file',   'label' => 'Kuwait ID',   'prefix' => 'kwid', 'field' => 'kuwait_id_file'],
        'passport'    => ['column' => 'passport_file',    'label' => 'Passport',    'prefix' => 'pass', 'field' => 'passport_file'],
        'work_permit' => ['column' => 'work_permit_file', 'label' => 'Work Permit', 'prefix' => 'wp',   'field' => 'work_permit_file'],
    ];

    private Database $db;
    private Employee $employeeModel;

    public function __construct() {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->employeeModel = new Employee();
        Employee::ensureSchema($this->db);
    }

    public function index(): void {
        Auth::authorize('employees', 'view');
        if (!$this->schemaReady()) {
            $this->schemaFailureRedirect();
        }

        $whId   = $this->requireWarehouseId();
        $search = $this->inputSearch('search');
        $status = $this->input('status', 'active', 'get', 20);
        if (!in_array($status, ['active', 'inactive', 'all'], true)) {
            $status = 'active';
        }
        $resFilter = $this->input('residence', 'all', 'get', 20);
        if (!in_array($resFilter, ['all', 'expired', 'due_soon'], true)) {
            $resFilter = 'all';
        }

        $where  = 'WHERE e.warehouse_id = ?';
        $params = [$whId];
        if ($status === 'active') {
            $where .= ' AND e.is_active = 1';
        } elseif ($status === 'inactive') {
            $where .= ' AND e.is_active = 0';
        }
        if ($search !== '') {
            $like = '%' . $search . '%';
            $where .= ' AND (e.name LIKE ? OR e.employee_no LIKE ? OR e.passport_no LIKE ?
                OR e.kuwait_id_no LIKE ? OR e.phone LIKE ? OR e.job_title LIKE ?)';
            $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
        }
        if ($resFilter === 'expired') {
            $where .= ' AND e.residence_expires_on IS NOT NULL AND e.residence_expires_on < CURDATE()';
        } elseif ($resFilter === 'due_soon') {
            $where .= ' AND e.residence_expires_on IS NOT NULL AND e.residence_expires_on >= CURDATE()
                AND e.residence_expires_on <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)';
        }

        $rows = $this->db->fetchAll(
            "SELECT e.* FROM employees e
             $where
             ORDER BY e.is_active DESC, e.name ASC",
            $params
        ) ?: [];

        $expiryCounts = $this->employeeModel->residenceExpiryCounts($whId);
        $today        = date('Y-m-d');
        $dueLimit     = date('Y-m-d', strtotime('+30 days'));

        $pageTitle = 'Employees';
        $page      = 'employees';

        ob_start();
        include __DIR__ . '/../views/employees/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function create(): void {
        Auth::authorize('employees', 'add');
        if (!$this->schemaReady()) {
            $this->schemaFailureRedirect();
        }

        $employee  = null;
        $pageTitle = 'New Employee';
        $page      = 'employees';

        ob_start();
        include __DIR__ . '/../views/employees/form.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function store(): void {
        Auth::authorize('employees', 'add');
        if (!$this->isPost()) {
            $this->redirect('?page=employees&action=create');
        }
        if (!$this->schemaReady()) {
            $this->schemaFailureRedirect();
        }

        $whId = $this->requireWarehouseId();
        $data = $this->collectFields();
        if ($data['name'] === '') {
            $this->flash('error', 'Employee name is required.');
            $this->redirect('?page=employees&action=create');
        }
        if ($data['date_error']) {
            $this->flash('error', $data['date_error']);
            $this->redirect('?page=employees&action=create');
        }

        $files = [];
        foreach (self::DOC_TYPES as $key => $meta) {
            [$path, $err] = $this->processDocUpload($key, null);
            if ($err) {
                $this->deleteUploadedPaths($files);
                $this->flash('error', $err);
                $this->redirect('?page=employees&action=create');
            }
            $files[$meta['column']] = $path;
        }

        try {
            $no = Employee::nextNo($this->db, $whId);
            $id = (int) $this->db->insert(
                "INSERT INTO employees
                    (employee_no, warehouse_id, name, job_title, phone, nationality,
                     passport_no, passport_expires_on, kuwait_id_no, residence_expires_on, salary,
                     kuwait_id_file, passport_file, work_permit_file, notes, is_active, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1,?)",
                [
                    $no, $whId, $data['name'], $data['job_title'], $data['phone'], $data['nationality'],
                    $data['passport_no'], $data['passport_expires_on'], $data['kuwait_id_no'],
                    $data['residence_expires_on'], $data['salary'],
                    $files['kuwait_id_file'], $files['passport_file'], $files['work_permit_file'],
                    $data['notes'], Auth::id(),
                ]
            );
            if ($id <= 0) {
                throw new RuntimeException('Employee insert did not return an id.');
            }
        } catch (Throwable $e) {
            $this->deleteUploadedPaths($files);
            error_log('EmployeeController::store failed: ' . $e->getMessage());
            $this->flash('error', 'Could not save employee. Please try again.');
            $this->redirect('?page=employees&action=create');
        }

        $this->flash('success', 'Employee record created.');
        $this->redirect('?page=employees&action=view&id=' . $id);
    }

    public function edit(): void {
        Auth::authorize('employees', 'edit');
        $employee = $this->loadCurrent();
        $pageTitle = 'Edit Employee';
        $page      = 'employees';

        ob_start();
        include __DIR__ . '/../views/employees/form.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function update(): void {
        Auth::authorize('employees', 'edit');
        if (!$this->isPost()) {
            $this->redirect('?page=employees');
        }

        $employee = $this->loadCurrent('post');
        $data     = $this->collectFields();
        if ($data['name'] === '') {
            $this->flash('error', 'Employee name is required.');
            $this->redirect('?page=employees&action=edit&id=' . (int) $employee['id']);
        }
        if ($data['date_error']) {
            $this->flash('error', $data['date_error']);
            $this->redirect('?page=employees&action=edit&id=' . (int) $employee['id']);
        }

        $files = [];
        foreach (self::DOC_TYPES as $key => $meta) {
            $existing = (string) ($employee[$meta['column']] ?? '');
            [$path, $err] = $this->processDocUpload($key, $existing !== '' ? $existing : null);
            if ($err) {
                $this->flash('error', $err);
                $this->redirect('?page=employees&action=edit&id=' . (int) $employee['id']);
            }
            $files[$meta['column']] = $path;
        }

        $isActive = $this->input('is_active') === '0' ? 0 : 1;

        try {
            $this->db->execute(
                "UPDATE employees SET
                    name = ?, job_title = ?, phone = ?, nationality = ?,
                    passport_no = ?, passport_expires_on = ?, kuwait_id_no = ?, residence_expires_on = ?, salary = ?,
                    kuwait_id_file = ?, passport_file = ?, work_permit_file = ?,
                    notes = ?, is_active = ?
                 WHERE id = ? AND warehouse_id = ?",
                [
                    $data['name'], $data['job_title'], $data['phone'], $data['nationality'],
                    $data['passport_no'], $data['passport_expires_on'], $data['kuwait_id_no'],
                    $data['residence_expires_on'], $data['salary'],
                    $files['kuwait_id_file'], $files['passport_file'], $files['work_permit_file'],
                    $data['notes'], $isActive,
                    (int) $employee['id'], $this->requireWarehouseId(),
                ]
            );
        } catch (Throwable $e) {
            error_log('EmployeeController::update failed: ' . $e->getMessage());
            $this->flash('error', 'Could not update employee. Please try again.');
            $this->redirect('?page=employees&action=edit&id=' . (int) $employee['id']);
        }

        $this->flash('success', 'Employee record updated.');
        $this->redirect('?page=employees&action=view&id=' . (int) $employee['id']);
    }

    public function view(): void {
        Auth::authorize('employees', 'view');
        $employee  = $this->loadCurrent();
        $pageTitle = (string) $employee['name'];
        $page      = 'employees';

        ob_start();
        include __DIR__ . '/../views/employees/view.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function delete(): void {
        Auth::authorize('employees', 'delete');
        if (!$this->isPost()) {
            $this->redirect('?page=employees');
        }

        $employee = $this->loadCurrent('post');
        foreach (self::DOC_TYPES as $meta) {
            $this->deleteDocFile((string) ($employee[$meta['column']] ?? ''));
        }
        $this->db->execute(
            'DELETE FROM employees WHERE id = ? AND warehouse_id = ?',
            [(int) $employee['id'], $this->requireWarehouseId()]
        );

        $this->flash('success', 'Employee record deleted.');
        $this->redirect('?page=employees');
    }

    /** Authenticated download of a stored document. */
    public function download(): void {
        Auth::authorize('employees', 'view');
        $employee = $this->loadCurrent();
        $doc      = preg_replace('/[^a-z_]/', '', strtolower($this->input('doc', '', 'get', 30)));
        if (!isset(self::DOC_TYPES[$doc])) {
            $this->flash('error', 'Unknown document type.');
            $this->redirect('?page=employees&action=view&id=' . (int) $employee['id']);
        }

        $rel = str_replace(['\\', '..'], ['/', ''], (string) ($employee[self::DOC_TYPES[$doc]['column']] ?? ''));
        if ($rel === '' || !str_starts_with($rel, 'employees/')) {
            $this->flash('error', 'No file on record for ' . self::DOC_TYPES[$doc]['label'] . '.');
            $this->redirect('?page=employees&action=view&id=' . (int) $employee['id']);
        }

        $abs      = rtrim(UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        $realBase = realpath(rtrim(UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . 'employees');
        $realFile = realpath($abs);
        if ($realBase === false || $realFile === false || !str_starts_with($realFile, $realBase . DIRECTORY_SEPARATOR)) {
            $this->flash('error', 'File is missing.');
            $this->redirect('?page=employees&action=view&id=' . (int) $employee['id']);
        }

        $mime = $this->detectUploadedMime($realFile) ?? 'application/octet-stream';
        $name = basename($realFile);

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($realFile));
        header('Content-Disposition: inline; filename="' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $name) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($realFile);
        exit;
    }

    /** @return array<string,mixed> */
    private function loadCurrent(string $from = 'get'): array {
        if (!$this->schemaReady()) {
            $this->schemaFailureRedirect();
        }
        $id = $this->inputInt('id', 0, $from) ?: $this->inputInt('id', 0, 'get');
        $row = $this->employeeModel->findInWarehouse($id, $this->requireWarehouseId());
        if (!$row) {
            $this->flash('error', 'Employee not found.');
            $this->redirect('?page=employees');
        }
        return $row;
    }

    /**
     * @return array{name:string,job_title:?string,phone:?string,nationality:?string,passport_no:?string,passport_expires_on:?string,kuwait_id_no:?string,residence_expires_on:?string,salary:float,notes:?string,date_error:?string}
     */
    private function collectFields(): array {
        $name = trim($this->input('name', '', 'post', 150));
        $job  = $this->nullable('job_title', 100);
        $phone = $this->nullable('phone', 40);
        $natRaw = trim($this->input('nationality', '', 'post', 40));
        $nat  = in_array($natRaw, Employee::NATIONALITIES, true) ? $natRaw : null;
        $pass = $this->nullable('passport_no', 50);
        $cid  = $this->nullable('kuwait_id_no', 30);
        $notes = $this->nullable('notes', 4000);
        $salary = $this->inputFloat('salary');
        if ($salary < 0) {
            $salary = 0.0;
        }

        [$residenceExpires, $resErr] = $this->parseOptionalDate('residence_expires_on', 'Residence expiry date is invalid.');
        [$passportExpires, $passErr] = $this->parseOptionalDate('passport_expires_on', 'Passport expiry date is invalid.');

        return [
            'name'                 => $name,
            'job_title'            => $job,
            'phone'                => $phone,
            'nationality'          => $nat,
            'passport_no'          => $pass,
            'passport_expires_on'  => $passportExpires,
            'kuwait_id_no'         => $cid,
            'residence_expires_on' => $residenceExpires,
            'salary'               => $salary,
            'notes'                => $notes,
            'date_error'           => $resErr ?? $passErr,
        ];
    }

    /** @return array{0:?string,1:?string} [Y-m-d or null, error or null] */
    private function parseOptionalDate(string $key, string $invalidMessage): array {
        $raw = trim($this->input($key, '', 'post', 20));
        if ($raw === '') {
            return [null, null];
        }
        $dt = DateTime::createFromFormat('Y-m-d', $raw);
        if (!$dt || $dt->format('Y-m-d') !== $raw) {
            return [null, $invalidMessage];
        }
        return [$raw, null];
    }

    private function nullable(string $key, int $maxLen): ?string {
        $v = trim($this->input($key, '', 'post', $maxLen));
        return $v === '' ? null : $v;
    }

    /**
     * @return array{0:?string,1:?string} [relative path or existing, error]
     */
    private function processDocUpload(string $docKey, ?string $existingFile): array {
        $meta   = self::DOC_TYPES[$docKey];
        $label  = $meta['label'];
        $remove = !empty($_POST['remove_' . $docKey]);
        $file   = $_FILES[$meta['field']] ?? null;
        $hasUpload = is_array($file)
            && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        if ($remove && !$hasUpload) {
            if ($existingFile) {
                $this->deleteDocFile($existingFile);
            }
            return [null, null];
        }
        if (!$hasUpload) {
            return [$existingFile, null];
        }

        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            return [$existingFile, $label . ' upload failed. Please try again.'];
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::DOC_MAX_BYTES) {
            return [$existingFile, $label . ' must be a PDF or image under 5 MB.'];
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return [$existingFile, $label . ' upload was invalid.'];
        }

        $mime = $this->detectUploadedMime($tmp);
        if ($mime === null || !isset(self::DOC_MIME[$mime])) {
            return [$existingFile, $label . ' must be PDF, JPG, PNG, or WEBP.'];
        }

        $dir = rtrim(UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . 'employees';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return [$existingFile, 'Could not create upload folder for employee documents.'];
        }
        $this->ensureUploadDenyFile($dir);

        $basename = $meta['prefix'] . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . self::DOC_MIME[$mime];
        $destAbs  = $dir . DIRECTORY_SEPARATOR . $basename;
        if (!move_uploaded_file($tmp, $destAbs)) {
            return [$existingFile, 'Could not save ' . $label . ' file.'];
        }

        if ($existingFile) {
            $this->deleteDocFile($existingFile);
        }

        return ['employees/' . $basename, null];
    }

    private function ensureUploadDenyFile(string $dir): void {
        $ht = $dir . DIRECTORY_SEPARATOR . '.htaccess';
        if (is_file($ht)) {
            return;
        }
        @file_put_contents(
            $ht,
            "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n"
        );
    }

    private function detectUploadedMime(string $path): ?string {
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($path);
            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        }
        if (function_exists('mime_content_type')) {
            $mime = @mime_content_type($path);
            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        }
        return null;
    }

    private function deleteDocFile(?string $relativePath): void {
        if ($relativePath === null || $relativePath === '') {
            return;
        }
        $relativePath = str_replace(['\\', '..'], ['/', ''], $relativePath);
        if (!str_starts_with($relativePath, 'employees/')) {
            return;
        }
        $abs = rtrim(UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        if (is_file($abs)) {
            @unlink($abs);
        }
    }

    /** @param array<string,?string> $paths */
    private function deleteUploadedPaths(array $paths): void {
        foreach ($paths as $p) {
            $this->deleteDocFile($p);
        }
    }

    private function requireWarehouseId(): int {
        $whId = (int) (Auth::warehouseId() ?? 0);
        if ($whId <= 0) {
            $this->flash('error', 'Select a warehouse first.');
            $this->redirect('?page=warehouse');
        }
        return $whId;
    }

    private function schemaReady(): bool {
        return Employee::ensureSchema($this->db);
    }

    private function schemaFailureRedirect(): void {
        $this->flash('error', 'Employee table could not be created. Check database permissions.');
        $this->redirect('?page=dashboard');
    }
}
