<?php

/**
 * Resilient loader for ledger accounts (cash/bank/wallet).
 * Tolerates missing columns, empty tables, and older deployed schemas.
 */
class AccountLedgerLoader {

    /**
     * @return list<array<string,mixed>>
     */
    public static function listForDropdown(Database $db): array {
        self::retireCashCustodyModule($db);
        self::renameKnetWarbaAccount($db);
        $queries = [
            'SELECT id, name, type, current_balance, is_default, is_active, gl_code, opening_balance, sort_order FROM accounts ORDER BY name ASC',
            'SELECT id, name, type, current_balance, is_default, is_active FROM accounts ORDER BY name ASC',
            'SELECT id, name, type, current_balance FROM accounts ORDER BY id ASC',
            'SELECT id, name FROM accounts ORDER BY id ASC',
            'SELECT * FROM accounts ORDER BY id ASC',
        ];

        $rows = self::fetchAccountRows($db, $queries);
        if ($rows !== null) {
            if ($rows === []) {
                self::seedIfEmpty($db);
                $rows = self::fetchAccountRows($db, $queries) ?? [];
            }
            return self::normalizeRows($rows);
        }

        self::ensureTable($db);
        self::seedIfEmpty($db);
        return self::normalizeRows(self::fetchAccountRows($db, $queries) ?? []);
    }

    /**
     * @param list<string> $queries
     * @return list<array<string,mixed>>|null  null = accounts table missing
     */
    private static function fetchAccountRows(Database $db, array $queries): ?array {
        foreach ($queries as $sql) {
            try {
                return $db->fetchAll($sql);
            } catch (PDOException $e) {
                $errno = (int) ($e->errorInfo[1] ?? 0);
                if ($errno === 1146) {
                    return null;
                }
                if ($errno !== 1054) {
                    throw $e;
                }
            }
        }
        return [];
    }

    public static function ensureTable(Database $db): void {
        try {
            $db->fetchOne('SELECT 1 FROM accounts LIMIT 1');
            return;
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) !== 1146) {
                throw $e;
            }
        }

        $db->execute(
            "CREATE TABLE IF NOT EXISTS accounts (
                id              INT AUTO_INCREMENT PRIMARY KEY,
                name            VARCHAR(255) NOT NULL,
                type            ENUM('cash', 'bank', 'mobile_wallet', 'other') DEFAULT 'cash',
                gl_code         VARCHAR(10) DEFAULT NULL,
                account_no      VARCHAR(100) DEFAULT NULL,
                bank_name       VARCHAR(255) DEFAULT NULL,
                opening_balance DECIMAL(15,3) DEFAULT 0.000,
                current_balance DECIMAL(15,3) DEFAULT 0.000,
                is_default      TINYINT(1) DEFAULT 0,
                is_active       TINYINT(1) DEFAULT 1,
                sort_order      INT DEFAULT 0,
                notes           TEXT DEFAULT NULL,
                created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        error_log('[ERP] Created missing accounts table.');
    }

    public static function seedIfEmpty(Database $db): void {
        try {
            $count = (int) ($db->fetchOne('SELECT COUNT(*) AS c FROM accounts')['c'] ?? 0);
        } catch (Throwable $e) {
            error_log('[ERP] AccountLedgerLoader seed count: ' . $e->getMessage());
            return;
        }
        if ($count > 0) {
            return;
        }

        $defaults = [
            ['Main Cash', 'cash', 1],
            ['Bank Account', 'bank', 0],
        ];

        foreach ($defaults as [$name, $type, $isDefault]) {
            $attempts = [
                ['INSERT INTO accounts (name, type, is_default, opening_balance, current_balance, is_active) VALUES (?,?,?,?,?,1)', [$name, $type, $isDefault, 0, 0]],
                ['INSERT INTO accounts (name, type, is_default, current_balance, is_active) VALUES (?,?,?,?,1)', [$name, $type, $isDefault, 0]],
                ['INSERT INTO accounts (name, type, is_default, current_balance) VALUES (?,?,?,?)', [$name, $type, $isDefault, 0]],
                ['INSERT INTO accounts (name, type, current_balance) VALUES (?,?,?)', [$name, $type, 0]],
                ['INSERT INTO accounts (name, type) VALUES (?,?)', [$name, $type]],
                ['INSERT INTO accounts (name) VALUES (?)', [$name]],
            ];
            foreach ($attempts as [$sql, $params]) {
                try {
                    $db->insert($sql, $params);
                    break;
                } catch (PDOException $e) {
                    if ((int) ($e->errorInfo[1] ?? 0) !== 1054) {
                        error_log('[ERP] AccountLedgerLoader seed insert: ' . $e->getMessage());
                        break;
                    }
                }
            }
        }

        error_log('[ERP] Seeded default ledger accounts (Main Cash, Bank Account).');
    }

    /**
     * @param list<array<string,mixed>> $rows
     * @return list<array<string,mixed>>
     */
    public static function normalizeRows(array $rows): array {
        foreach ($rows as &$acc) {
            $acc['id']               = (int) ($acc['id'] ?? 0);
            $acc['name']             = trim((string) ($acc['name'] ?? ''));
            $acc['type']             = (string) ($acc['type'] ?? 'cash');
            $acc['gl_code']          = $acc['gl_code'] ?? null;
            $acc['opening_balance']  = (float) ($acc['opening_balance'] ?? 0);
            $acc['current_balance']  = (float) ($acc['current_balance'] ?? 0);
            $acc['is_default']       = (int) ($acc['is_default'] ?? 0);
            $acc['sort_order']       = (int) ($acc['sort_order'] ?? 0);
            $acc['is_active']        = (int) ($acc['is_active'] ?? 1);
        }
        unset($acc);

        return $rows;
    }

    /**
     * One-time: drop Cash Custody tables/permissions and remove the Custody cash account.
     * Remaining balance is added back to Main Cash when the account can be deleted.
     */
    public static function retireCashCustodyModule(Database $db): void {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        try {
            $db->execute('DROP TABLE IF EXISTS cash_handover_payments');
            $db->execute('DROP TABLE IF EXISTS cash_handovers');
        } catch (Throwable $e) {
            error_log('[ERP] retire cash_handovers: ' . $e->getMessage());
        }

        try {
            $db->execute("DELETE FROM permissions WHERE module = 'cash_custody'");
        } catch (Throwable $e) {
            error_log('[ERP] retire cash_custody permissions: ' . $e->getMessage());
        }

        try {
            $custodyRows = $db->fetchAll(
                "SELECT * FROM accounts
                 WHERE LOWER(TRIM(name)) IN ('custody', 'cash custody', 'safe')
                   AND type = 'cash'"
            );
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) !== 1054) {
                error_log('[ERP] retire custody accounts lookup: ' . $e->getMessage());
                return;
            }
            try {
                $custodyRows = $db->fetchAll(
                    "SELECT * FROM accounts
                     WHERE LOWER(TRIM(name)) IN ('custody', 'cash custody', 'safe')"
                );
            } catch (Throwable $e2) {
                error_log('[ERP] retire custody accounts lookup: ' . $e2->getMessage());
                return;
            }
        } catch (Throwable $e) {
            error_log('[ERP] retire custody accounts lookup: ' . $e->getMessage());
            return;
        }

        if ($custodyRows === []) {
            return;
        }

        $main = self::findMainCashForRetirement($db, $custodyRows);

        foreach ($custodyRows as $row) {
            $cid = (int) ($row['id'] ?? 0);
            if ($cid <= 0) {
                continue;
            }
            $mainId = $main !== null ? (int) $main['id'] : 0;
            if ($mainId === $cid) {
                continue;
            }

            try {
                $db->beginTransaction();
                $locked = $db->fetchOne('SELECT * FROM accounts WHERE id = ? FOR UPDATE', [$cid]);
                if (!$locked) {
                    $db->rollback();
                    continue;
                }

                $payments    = self::countForAccount($db, 'payments', 'account_id', $cid);
                $expenses    = self::countForAccount($db, 'expenses', 'account_id', $cid);
                $adjustments = self::countForAccount($db, 'account_balance_adjustments', 'account_id', $cid);
                $pos         = self::countForAccount($db, 'purchase_orders', 'account_id', $cid);
                $otherXfers  = self::countCustodyOtherTransfers($db, $cid, $mainId);
                $blocked     = ($payments + $expenses + $adjustments + $pos + $otherXfers) > 0;

                $bal = (float) ($locked['current_balance'] ?? 0);

                if ($blocked) {
                    if (abs($bal) > 0.0005 && $mainId > 0) {
                        self::moveCustodyBalanceToMain($db, $cid, $mainId, $bal);
                    }
                    try {
                        $db->execute('UPDATE accounts SET is_active = 0 WHERE id = ?', [$cid]);
                    } catch (PDOException $e) {
                        if ((int) ($e->errorInfo[1] ?? 0) !== 1054) {
                            throw $e;
                        }
                    }
                    $db->commit();
                    continue;
                }

                if (abs($bal) > 0.0005 && $mainId > 0) {
                    $db->execute(
                        'UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?',
                        [$bal, $mainId]
                    );
                }

                $db->execute(
                    'DELETE FROM account_transfers WHERE from_account_id = ? OR to_account_id = ?',
                    [$cid, $cid]
                );
                $db->execute('DELETE FROM accounts WHERE id = ?', [$cid]);
                $db->commit();
            } catch (Throwable $e) {
                try {
                    $db->rollback();
                } catch (Throwable $ignored) {
                }
                error_log('[ERP] retire custody account ' . $cid . ': ' . $e->getMessage());
            }
        }
    }

    /** @param list<array<string,mixed>> $custodyRows */
    private static function findMainCashForRetirement(Database $db, array $custodyRows): ?array {
        $skip = [];
        foreach ($custodyRows as $row) {
            $skip[(int) ($row['id'] ?? 0)] = true;
        }

        $queries = [
            "SELECT * FROM accounts
             WHERE is_active = 1
               AND LOWER(TRIM(name)) = 'main cash'
             ORDER BY id ASC LIMIT 1",
            "SELECT * FROM accounts
             WHERE is_active = 1 AND is_default = 1 AND type = 'cash'
             ORDER BY id ASC LIMIT 1",
            "SELECT * FROM accounts
             WHERE LOWER(TRIM(name)) = 'main cash'
             ORDER BY id ASC LIMIT 1",
        ];
        foreach ($queries as $sql) {
            try {
                $row = $db->fetchOne($sql);
            } catch (PDOException $e) {
                if ((int) ($e->errorInfo[1] ?? 0) !== 1054) {
                    throw $e;
                }
                continue;
            }
            if ($row && empty($skip[(int) ($row['id'] ?? 0)])) {
                return $row;
            }
        }
        return null;
    }

    private static function countForAccount(Database $db, string $table, string $column, int $id): int {
        try {
            $row = $db->fetchOne("SELECT COUNT(*) AS c FROM {$table} WHERE {$column} = ?", [$id]);
            return (int) ($row['c'] ?? 0);
        } catch (Throwable $e) {
            return 0;
        }
    }

    private static function countCustodyOtherTransfers(Database $db, int $custodyId, int $mainId): int {
        try {
            if ($mainId > 0) {
                $row = $db->fetchOne(
                    "SELECT COUNT(*) AS c FROM account_transfers
                     WHERE (from_account_id = ? OR to_account_id = ?)
                       AND from_account_id <> ? AND to_account_id <> ?",
                    [$custodyId, $custodyId, $mainId, $mainId]
                );
            } else {
                $row = $db->fetchOne(
                    'SELECT COUNT(*) AS c FROM account_transfers
                     WHERE from_account_id = ? OR to_account_id = ?',
                    [$custodyId, $custodyId]
                );
            }
            return (int) ($row['c'] ?? 0);
        } catch (Throwable $e) {
            return 0;
        }
    }

    private static function moveCustodyBalanceToMain(Database $db, int $fromId, int $toId, float $amount): void {
        $row = $db->fetchOne(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(transfer_no, 5) AS UNSIGNED)), 0) AS max_no
             FROM account_transfers
             WHERE transfer_no LIKE 'TRF-%'
             FOR UPDATE"
        );
        $transferNo = 'TRF-' . str_pad((string) ((int) ($row['max_no'] ?? 0) + 1), 6, '0', STR_PAD_LEFT);
        $db->execute('UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?', [$amount, $fromId]);
        $db->execute('UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?', [$amount, $toId]);
        $db->insert(
            'INSERT INTO account_transfers (transfer_no, from_account_id, to_account_id, amount, date, notes)
             VALUES (?,?,?,?,?,?)',
            [$transferNo, $fromId, $toId, $amount, date('Y-m-d'), 'Retire Custody account — balance returned to Main Cash']
        );
    }

    /** One-time: display name Knet warba → Knet - Warba Bank. */
    public static function renameKnetWarbaAccount(Database $db): void {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $newName = 'Knet - Warba Bank';
        try {
            $taken = $db->fetchOne(
                "SELECT id FROM accounts WHERE LOWER(TRIM(name)) = ? LIMIT 1",
                [strtolower($newName)]
            );
            $olds = $db->fetchAll(
                "SELECT id FROM accounts
                 WHERE LOWER(TRIM(name)) IN ('knet warba', 'knet-warba', 'knet warba bank')"
            );
        } catch (Throwable $e) {
            error_log('[ERP] rename Knet Warba: ' . $e->getMessage());
            return;
        }

        $takenId = $taken ? (int) ($taken['id'] ?? 0) : 0;
        foreach ($olds as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            if ($takenId > 0 && $takenId !== $id) {
                continue;
            }
            try {
                $db->execute('UPDATE accounts SET name = ? WHERE id = ?', [$newName, $id]);
                $takenId = $id;
            } catch (Throwable $e) {
                error_log('[ERP] rename Knet Warba id ' . $id . ': ' . $e->getMessage());
            }
        }
    }
}
