<?php
/**
 * Local-only helper: test remote connection to the Hostinger MySQL DB.
 * Usage: php -d extension=pdo_mysql tools/remote_db_test.php [host]
 * Reads credentials from .env; host defaults to 77.37.48.59.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$envFile = __DIR__ . '/../.env';
if (!is_file($envFile)) {
    fwrite(STDERR, ".env not found\n");
    exit(1);
}

$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (strpos($line, '=') === false || $line[0] === '#') {
        continue;
    }
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v);
}

$host = $argv[1] ?? '77.37.48.59';
$port = $env['DB_PORT'] ?? '3306';
$name = $env['DB_NAME'] ?? '';
$user = $env['DB_USER'] ?? '';
$pass = $env['DB_PASS'] ?? '';

echo "Connecting to {$host}:{$port} / {$name} as {$user} ...\n";

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 10]
    );
    $ver = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo "CONNECTED. MySQL version: {$ver}\n";

    $acc = $pdo->query('SELECT id, name, current_balance FROM accounts WHERE id = 9')->fetch(PDO::FETCH_ASSOC);
    echo 'Account 9: ' . json_encode($acc) . "\n";
} catch (PDOException $e) {
    echo 'FAILED: ' . $e->getMessage() . "\n";
    exit(1);
}
