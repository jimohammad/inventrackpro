<?php

require_once __DIR__ . '/BaseController.php';

class SettingsController extends BaseController {
    public function index(): void {
        Auth::authorize('settings', 'view');
        $db       = Database::getInstance();
        $settings = [];
        $rows     = $db->fetchAll("SELECT key_name, value FROM settings");
        foreach ($rows as $r) { $settings[$r['key_name']] = $r['value']; }

        if ($this->isPost()) {
            // Whitelist allowed setting keys to prevent mass assignment
            $allowedKeys = [
                'company_name', 'company_address', 'company_phone', 'company_email',
                'company_logo', 'invoice_footer', 'invoice_terms', 'currency',
                'decimal_places', 'whatsapp_phone_id', 'whatsapp_token', 'whatsapp_recipient',
                'admin_pin', 'default_warehouse', 'default_account',
                'sale_prefix', 'purchase_prefix', 'return_prefix', 'expense_prefix',
                'transfer_prefix', 'payment_prefix', 'tax_rate', 'tax_label',
            ];
            foreach ($_POST['settings'] ?? [] as $key => $val) {
                $key = htmlspecialchars($key);
                if (!in_array($key, $allowedKeys)) continue;
                if ($key === 'admin_pin' && trim($val) === '') continue; // keep existing PIN
                if ($key === 'admin_pin') {
                    $val = password_hash(trim((string) $val), PASSWORD_DEFAULT);
                }
                $db->execute(
                    "INSERT INTO settings (key_name, value) VALUES (?,?)
                     ON DUPLICATE KEY UPDATE value = ?",
                    [$key, trim($val), trim($val)]
                );
            }
            $this->flash('success', 'Settings saved.');
            $this->redirect('?page=settings');
        }

        $accounts   = self::getAccounts();
        $warehouses = self::getWarehouses();
        $pageTitle  = 'Settings';
        $page       = 'settings';

        ob_start();
        include __DIR__ . '/../views/settings/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layout.php';
    }

    // AJAX: Toggle public pricelist visibility (3 minutes)
    public function togglePricelist(): void {
        header('Content-Type: application/json');
        if (!$this->isPost()) { echo json_encode(['error' => 'POST required']); return; }

        $db     = Database::getInstance();
        $action = trim($_POST['action_type'] ?? 'on');

        if ($action === 'on') {
            $until = date('Y-m-d H:i:s', time() + 180); // 3 minutes from now
            $db->execute(
                "INSERT INTO settings (key_name, value) VALUES ('pricelist_visible_until', ?)
                 ON DUPLICATE KEY UPDATE value = ?",
                [$until, $until]
            );
            echo json_encode(['success' => true, 'until' => $until, 'seconds' => 180]);
        } else {
            $db->execute(
                "INSERT INTO settings (key_name, value) VALUES ('pricelist_visible_until', '2000-01-01')
                 ON DUPLICATE KEY UPDATE value = '2000-01-01'",
                []
            );
            echo json_encode(['success' => true, 'until' => null, 'seconds' => 0]);
        }
    }

    // AJAX: Check pricelist status
    public function pricelistStatus(): void {
        header('Content-Type: application/json');
        $db  = Database::getInstance();
        $row = $db->fetchOne("SELECT value FROM settings WHERE key_name = 'pricelist_visible_until'");
        $until = $row['value'] ?? '2000-01-01';
        $remaining = max(0, strtotime($until) - time());
        echo json_encode(['active' => $remaining > 0, 'seconds' => $remaining]);
    }

    public function verifyPin(): void {
        header('Content-Type: application/json');
        if (!$this->isPost()) { echo json_encode(['valid' => false]); return; }
        Auth::authorize('settings', 'view');

        $now = time();
        $fails = (int)($_SESSION['admin_pin_fails'] ?? 0);
        $lockUntil = (int)($_SESSION['admin_pin_lock_until'] ?? 0);
        if ($lockUntil > $now) {
            echo json_encode([
                'valid' => false,
                'locked' => true,
                'retry_after' => $lockUntil - $now,
            ]);
            return;
        }

        // Accept PIN via POST for security (avoids URL logging)
        $pin = trim($_POST['pin'] ?? '');
        $db  = Database::getInstance();
        $stored = $db->fetchOne("SELECT value FROM settings WHERE key_name = 'admin_pin'");
        $adminPin = $stored['value'] ?? '0000';

        $valid = false;
        $looksHashed = is_string($adminPin) && substr($adminPin, 0, 2) === '$2';
        if ($looksHashed) {
            $valid = password_verify($pin, $adminPin);
        } else {
            // Backward compatibility with legacy plain-text pins.
            $valid = hash_equals((string) $adminPin, (string) $pin);
            if ($valid) {
                // Opportunistic migration to a password hash once the correct PIN is presented.
                $hash = password_hash($pin, PASSWORD_DEFAULT);
                $db->execute(
                    "INSERT INTO settings (key_name, value) VALUES ('admin_pin', ?)
                     ON DUPLICATE KEY UPDATE value = ?",
                    [$hash, $hash]
                );
            }
        }

        if ($valid) {
            unset($_SESSION['admin_pin_fails'], $_SESSION['admin_pin_lock_until']);
            echo json_encode(['valid' => true]);
            return;
        }

        $fails++;
        $_SESSION['admin_pin_fails'] = $fails;
        if ($fails >= 5) {
            $_SESSION['admin_pin_lock_until'] = $now + 30;
            echo json_encode(['valid' => false, 'locked' => true, 'retry_after' => 30]);
            return;
        }
        echo json_encode(['valid' => false]);
    }

    /**
     * Session-only default print template (no DB changes).
     * Values: a5 | thermal
     */
    public function setPrintTemplate(): void {
        header('Content-Type: application/json');
        if (!$this->isPost()) { echo json_encode(['error' => 'POST required']); return; }
        Auth::authorize('settings', 'view');

        $tpl = strtolower(trim((string)($_POST['tpl'] ?? 'a5')));
        if (!in_array($tpl, ['a5', 'thermal'], true)) {
            $tpl = 'a5';
        }
        $_SESSION['print_template'] = $tpl;
        echo json_encode(['success' => true, 'template' => $tpl]);
    }
}
