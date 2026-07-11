<?php
/**
 * Universal request-cost probe — no auth, no secrets in output.
 * Upload/run on server OR locally: php tools/bootstrap_timer.php
 * Optional: php tools/bootstrap_timer.php --with-db
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$withDb = in_array('--with-db', $argv ?? [], true);
$root   = dirname(__DIR__);
$t0     = microtime(true);
$marks  = [];

$mark = static function (string $label) use (&$marks, $t0): void {
    $marks[] = [$label, (int) round((microtime(true) - $t0) * 1000)];
};

$mark('start');

require_once $root . '/config/app.php';
$mark('config/app.php');

require_once $root . '/app/helpers/WebExceptionHandler.php';
$mark('WebExceptionHandler.php');

require_once $root . '/config/database.php';
$mark('config/database.php (env + class)');

require_once $root . '/app/helpers/Auth.php';
$mark('Auth.php');

require_once $root . '/app/helpers/ListPage.php';
$mark('ListPage.php');

require_once $root . '/app/controllers/BaseController.php';
$mark('BaseController.php');

$controllerFile = $root . '/app/controllers/SalesController.php';
require_once $controllerFile;
$mark('SalesController.php');

if ($withDb) {
    try {
        $db = Database::getInstance();
        $mark('Database::getInstance() + connect');

        $db->fetchOne('SELECT 1 AS ok');
        $mark('SELECT 1');

        $db->fetchOne(
            "SELECT COUNT(*) AS c FROM sales WHERE warehouse_id = 1 AND date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"
        );
        $mark('sample sales COUNT (indexed list filter)');
    } catch (Throwable $e) {
        $marks[] = ['DB ERROR: ' . $e->getMessage(), (int) round((microtime(true) - $t0) * 1000)];
    }
}

echo "=== Bootstrap timer ===\n";
echo 'Project root: ' . $root . "\n";
echo 'On synced drive: ' . (stripos($root, 'My Drive') !== false || stripos($root, 'Google Drive') !== false ? 'YES' : 'no') . "\n";
if ($withDb) {
    echo 'DB_HOST: ' . DB_HOST . ' (use localhost on Hostinger, not public IP)' . "\n";
}
echo "\n";

$prev = 0;
foreach ($marks as [$label, $ms]) {
    $delta = $ms - $prev;
    echo str_pad($label, 44) . str_pad((string) $ms, 6, ' ', STR_PAD_LEFT) . ' ms  (+' . $delta . " ms)\n";
    $prev = $ms;
}

echo "\nInterpretation:\n";
echo "- +config/database.php > 50ms: .env read slow or path issue\n";
echo "- +getInstance > 200ms: DB connect slow — check DB_HOST=localhost on server\n";
echo "- Large gap before any require: PHP/opcache or synced-folder I/O\n";
