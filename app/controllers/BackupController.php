<?php

require_once __DIR__ . '/BaseController.php';

class BackupController extends BaseController {
    public function index(): void {
        if (!Auth::isAdmin()) { $this->redirect('?page=dashboard'); }

        $db        = Database::getInstance();
        $logs      = $db->fetchAll("SELECT b.*, u.name as created_by_name FROM backup_logs b LEFT JOIN users u ON u.id = b.created_by ORDER BY b.created_at DESC LIMIT 20");
        $pageTitle = 'Backups';
        $page      = 'backups';

        ob_start();
        include __DIR__ . '/../views/settings/backups.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    public function run(): void {
        if (!Auth::isAdmin()) { $this->redirect('?page=dashboard'); return; }
        if (!$this->isPost()) { $this->redirect('?page=backups'); return; }

        $backupDir = __DIR__ . '/../../../backups/';
        if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $backupDir . $filename;

        $cmd = sprintf(
            'mysqldump --host=%s --user=%s --password=%s %s > %s 2>&1',
            escapeshellarg(DB_HOST),
            escapeshellarg(DB_USER),
            escapeshellarg(DB_PASS),
            escapeshellarg(DB_NAME),
            escapeshellarg($filepath)
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && file_exists($filepath)) {
            $size = (int)(filesize($filepath) / 1024);
            $db   = Database::getInstance();
            $db->insert(
                "INSERT INTO backup_logs (filename, size_kb, created_by) VALUES (?,?,?)",
                [$filename, $size, Auth::id()]
            );
            $this->flash('success', "Backup created: {$filename} ({$size} KB)");
        } else {
            $this->flash('error', 'Backup failed. Check server permissions or mysqldump availability.');
        }

        $this->redirect('?page=backups');
    }

    public function download(): void {
        if (!Auth::isAdmin()) exit;

        $file    = basename($this->input('file', '', 'get'));
        $path    = __DIR__ . '/../../../backups/' . $file;

        if (!file_exists($path) || !str_starts_with($file, 'backup_') || !str_ends_with($file, '.sql')) {
            die('File not found.');
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
}
