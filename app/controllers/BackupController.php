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

        // SEC4 (defense in depth): drop a deny-all .htaccess inside the backups dir
        // so a misconfigured DocumentRoot can't expose backups via the web.
        $htaccess = $backupDir . '.htaccess';
        if (!file_exists($htaccess)) {
            @file_put_contents(
                $htaccess,
                "Require all denied\n<IfModule !mod_authz_core.c>\n    Order Allow,Deny\n    Deny from all\n</IfModule>\n"
            );
        }

        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $backupDir . $filename;

        // SEC1 fix: pass DB password through MYSQL_PWD env var instead of --password=...
        // so it never lands in /proc/<pid>/cmdline or `ps aux` (other shared-host users
        // could read it from there).
        $cmd = sprintf(
            'mysqldump --host=%s --user=%s %s > %s 2>&1',
            escapeshellarg(DB_HOST),
            escapeshellarg(DB_USER),
            escapeshellarg(DB_NAME),
            escapeshellarg($filepath)
        );

        $env = $_ENV;
        $env['MYSQL_PWD'] = DB_PASS;

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $proc = proc_open($cmd, $descriptors, $pipes, null, $env);
        $returnCode = -1;
        if (is_resource($proc)) {
            fclose($pipes[0]);
            stream_get_contents($pipes[1]);
            stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $returnCode = proc_close($proc);
        }

        if ($returnCode === 0 && file_exists($filepath)) {
            @chmod($filepath, 0600);
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
