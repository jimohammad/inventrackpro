<?php

/**
 * Database Configuration
 * Credentials are loaded from .env file OUTSIDE the web root
 * 
 * On Hostinger, place .env at: /home/u793102776/.env
 * This keeps secrets out of the public_html folder and git repo
 */

// ── Load .env file ─────────────────────────────────────────
$envPaths = [
    __DIR__ . '/../.env.local',        // local dev override (gitignored, never on server)
    __DIR__ . '/../../.env',           // one level above public_html (recommended)
    __DIR__ . '/../.env',              // root of public_html (fallback)
    '/home/u793102776/.env',           // absolute Hostinger path
];

$envLoaded = false;
foreach ($envPaths as $envFile) {
    if (file_exists($envFile) && is_readable($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            if (strpos($line, '=') === false) continue;
            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);
            if (preg_match('/^(["\'])(.*)\\1$/', $value, $m)) {
                $value = $m[2];
            }
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
        $envLoaded = true;
        break;
    }
}

if (!$envLoaded) {
    error_log("CRITICAL: .env file not found. Checked: " . implode(', ', $envPaths));
    die(json_encode([
        'success' => false,
        'message' => 'Server configuration error. Please contact the administrator.'
    ]));
}

define('DB_HOST',    $_ENV['DB_HOST']    ?? 'localhost');
define('DB_PORT',    $_ENV['DB_PORT']    ?? '3306');
define('DB_NAME',    $_ENV['DB_NAME']    ?? '');
define('DB_USER',    $_ENV['DB_USER']    ?? '');
define('DB_PASS',    $_ENV['DB_PASS']    ?? '');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');

if (empty(DB_NAME) || empty(DB_USER)) {
    error_log("CRITICAL: DB_NAME or DB_USER is empty. Check .env file.");
    die(json_encode([
        'success' => false,
        'message' => 'Database not configured. Please contact the administrator.'
    ]));
}

/**
 * PDO Database Connection Class
 * Single instance (Singleton) - only one connection open at a time
 * Includes auto-reconnect logic to handle dropped connections
 */
class Database {
    private static ?Database $instance = null;
    private ?PDO $pdo = null;

    private function __construct() {
        $this->connect();
    }

    /**
     * Establish a fresh DB connection with retry logic
     */
    private function connect(int $retries = 3): void {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            // PDO::ATTR_PERSISTENT removed — persistent connections cause
            // "too many connections" errors on cloud/shared hosting
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::ATTR_TIMEOUT            => 10, // 10 second connection timeout
        ];

        $lastException = null;

        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            try {
                $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                $this->pdo->exec("SET time_zone = '+03:00'"); // Kuwait timezone
                return; // success — exit retry loop
            } catch (PDOException $e) {
                $lastException = $e;
                error_log("DB Connection attempt {$attempt} failed: " . $e->getMessage());
                if ($attempt < $retries) {
                    sleep(1); // wait 1 second before retrying
                }
            }
        }

        // All retries failed
        error_log("DB Connection Failed after {$retries} attempts: " . $lastException->getMessage());
        die(json_encode([
            'success' => false,
            'message' => 'Database connection failed. Please contact your administrator.'
        ]));
    }

    /**
     * Check if connection is still alive, reconnect if not
     */
    private function ensureConnected(): void {
        try {
            // Ping the server with a lightweight query
            $this->pdo->query('SELECT 1');
        } catch (PDOException $e) {
            error_log("DB connection lost, reconnecting... " . $e->getMessage());
            $this->pdo = null;
            $this->connect();
        }
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        $this->ensureConnected();
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): PDOStatement {
        $this->ensureConnected();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchOne(string $sql, array $params = []): array|false {
        return $this->query($sql, $params)->fetch();
    }

    public function fetchAll(string $sql, array $params = []): array {
        return $this->query($sql, $params)->fetchAll();
    }

    public function insert(string $sql, array $params = []): string|false {
        $this->query($sql, $params);
        return $this->pdo->lastInsertId();
    }

    public function execute(string $sql, array $params = []): int {
        return $this->query($sql, $params)->rowCount();
    }

    public function beginTransaction(): void {
        $this->ensureConnected();
        $this->pdo->beginTransaction();
    }

    public function commit(): void {
        $this->pdo->commit();
    }

    public function rollback(): void {
        $this->pdo->rollBack();
    }

    private function __clone() {}
}
