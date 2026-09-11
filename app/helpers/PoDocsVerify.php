<?php

/**
 * Signed public verification for PO bank document packs (supplier invoice + TT).
 * Token binds warehouse + period (or one PO) + file counts + document-id fingerprint.
 */
class PoDocsVerify {

    public const SETTING_KEY = 'po_docs_verify_secret';

    /**
     * @param list<int> $docIds
     */
    public static function fingerprint(array $docIds): string {
        $ids = [];
        foreach ($docIds as $id) {
            $n = (int) $id;
            if ($n > 0) {
                $ids[] = $n;
            }
        }
        $ids = array_values(array_unique($ids));
        sort($ids, SORT_NUMERIC);
        return substr(hash('sha256', implode(',', $ids)), 0, 10);
    }

    /**
     * Pack token: p1.wh.u|o.from.to.pos.inv.tt.fp.issued.sig
     */
    public static function signPack(
        Database $db,
        int $warehouseId,
        string $dateField,
        string $fromDate,
        string $toDate,
        int $poCount,
        int $invoiceCount,
        int $ttCount,
        string $fingerprint
    ): string {
        $df = $dateField === 'po' ? 'o' : 'u';
        $fromKey = str_replace('-', '', $fromDate);
        $toKey   = str_replace('-', '', $toDate);
        $issued  = date('Ymd');
        $fp      = self::safeFp($fingerprint);
        $body    = 'p1.' . $warehouseId . '.' . $df . '.' . $fromKey . '.' . $toKey . '.'
            . $poCount . '.' . $invoiceCount . '.' . $ttCount . '.' . $fp . '.' . $issued;
        return $body . '.' . self::hmac($db, $body);
    }

    /**
     * Single-PO token: p2.wh.poId.inv.tt.fp.issued.sig
     */
    public static function signPo(
        Database $db,
        int $warehouseId,
        int $poId,
        int $invoiceCount,
        int $ttCount,
        string $fingerprint
    ): string {
        $issued = date('Ymd');
        $fp     = self::safeFp($fingerprint);
        $body   = 'p2.' . $warehouseId . '.' . $poId . '.' . $invoiceCount . '.' . $ttCount . '.' . $fp . '.' . $issued;
        return $body . '.' . self::hmac($db, $body);
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function parse(Database $db, string $token): ?array {
        $token = trim($token);
        if ($token === '' || strlen($token) > 200) {
            return null;
        }
        $parts = explode('.', $token);
        $kind = $parts[0] ?? '';
        if ($kind === 'p1') {
            return self::parsePack($db, $parts);
        }
        if ($kind === 'p2') {
            return self::parsePo($db, $parts);
        }
        return null;
    }

    /**
     * @param list<string> $parts
     * @return array<string,mixed>|null
     */
    private static function parsePack(Database $db, array $parts): ?array {
        if (count($parts) !== 11) {
            return null;
        }
        [$ver, $wh, $df, $fromKey, $toKey, $pos, $inv, $tt, $fp, $issued, $sig] = $parts;
        unset($ver);
        if ($df !== 'u' && $df !== 'o') {
            return null;
        }
        if (!ctype_digit($wh) || !ctype_digit($fromKey) || !ctype_digit($toKey)
            || !ctype_digit($pos) || !ctype_digit($inv) || !ctype_digit($tt)
            || !ctype_xdigit($fp) || strlen($fp) !== 10
            || !ctype_digit($issued) || strlen($fromKey) !== 8 || strlen($toKey) !== 8 || strlen($issued) !== 8
        ) {
            return null;
        }
        $body = $parts[0] . '.' . $wh . '.' . $df . '.' . $fromKey . '.' . $toKey . '.'
            . $pos . '.' . $inv . '.' . $tt . '.' . $fp . '.' . $issued;
        if (!hash_equals(self::hmac($db, $body), $sig)) {
            return null;
        }
        $fromDate = self::ymd($fromKey);
        $toDate   = self::ymd($toKey);
        if ($fromDate === null || $toDate === null || $fromDate > $toDate) {
            return null;
        }
        return [
            'kind'          => 'pack',
            'warehouse_id'  => (int) $wh,
            'date_field'    => $df === 'o' ? 'po' : 'uploaded',
            'from_date'     => $fromDate,
            'to_date'       => $toDate,
            'po_count'      => (int) $pos,
            'invoice_count' => (int) $inv,
            'tt_count'      => (int) $tt,
            'fingerprint'   => strtolower($fp),
            'issued'        => self::ymd($issued),
        ];
    }

    /**
     * @param list<string> $parts
     * @return array<string,mixed>|null
     */
    private static function parsePo(Database $db, array $parts): ?array {
        if (count($parts) !== 8) {
            return null;
        }
        [$ver, $wh, $poId, $inv, $tt, $fp, $issued, $sig] = $parts;
        unset($ver);
        if (!ctype_digit($wh) || !ctype_digit($poId) || !ctype_digit($inv) || !ctype_digit($tt)
            || !ctype_xdigit($fp) || strlen($fp) !== 10
            || !ctype_digit($issued) || strlen($issued) !== 8
        ) {
            return null;
        }
        $body = $parts[0] . '.' . $wh . '.' . $poId . '.' . $inv . '.' . $tt . '.' . $fp . '.' . $issued;
        if (!hash_equals(self::hmac($db, $body), $sig)) {
            return null;
        }
        return [
            'kind'          => 'po',
            'warehouse_id'  => (int) $wh,
            'po_id'         => (int) $poId,
            'invoice_count' => (int) $inv,
            'tt_count'      => (int) $tt,
            'fingerprint'   => strtolower($fp),
            'issued'        => self::ymd($issued),
        ];
    }

    /**
     * Live PO documents for a date range (same rules as the bank report).
     *
     * @return array{
     *   po_count:int,invoice_count:int,tt_count:int,fingerprint:string,
     *   rows:list<array{po_no:string,po_date:string,invoice_count:int,tt_count:int}>
     * }
     */
    public static function livePack(Database $db, int $warehouseId, string $fromDate, string $toDate, string $dateField): array {
        $where = "WHERE d.warehouse_id = ?
                    AND po.warehouse_id = ?
                    AND po.status != 'cancelled'
                    AND d.doc_type IN ('supplier_invoice', 'money_transfer')";
        $params = [$warehouseId, $warehouseId];
        if ($dateField === 'po') {
            $where .= ' AND po.date BETWEEN ? AND ?';
            $params[] = $fromDate;
            $params[] = $toDate;
        } else {
            $toExclusive = (new DateTimeImmutable($toDate))->modify('+1 day')->format('Y-m-d');
            $where .= ' AND d.created_at >= ? AND d.created_at < ?';
            $params[] = $fromDate . ' 00:00:00';
            $params[] = $toExclusive . ' 00:00:00';
        }

        $files = $db->fetchAll(
            "SELECT d.id, d.po_id, d.doc_type, po.po_no, po.date AS po_date
             FROM purchase_order_documents d
             JOIN purchase_orders po ON po.id = d.po_id
             {$where}
             ORDER BY po.date ASC, po.id ASC,
                      CASE d.doc_type WHEN 'supplier_invoice' THEN 0 ELSE 1 END,
                      d.id ASC",
            $params
        ) ?: [];

        return self::summarizeRows($files);
    }

    /**
     * @return array{
     *   po_count:int,invoice_count:int,tt_count:int,fingerprint:string,cancelled:bool,
     *   po_no:?string,po_date:?string,
     *   rows:list<array{po_no:string,po_date:string,invoice_count:int,tt_count:int}>
     * }
     */
    public static function livePo(Database $db, int $warehouseId, int $poId): array {
        $po = $db->fetchOne(
            "SELECT id, po_no, date, status FROM purchase_orders
             WHERE id = ? AND warehouse_id = ?",
            [$poId, $warehouseId]
        );
        if (!$po) {
            return [
                'po_count' => 0, 'invoice_count' => 0, 'tt_count' => 0,
                'fingerprint' => self::fingerprint([]),
                'cancelled' => false, 'po_no' => null, 'po_date' => null, 'rows' => [],
            ];
        }
        $cancelled = (($po['status'] ?? '') === 'cancelled');
        $files = $db->fetchAll(
            "SELECT d.id, d.po_id, d.doc_type, po.po_no, po.date AS po_date
             FROM purchase_order_documents d
             JOIN purchase_orders po ON po.id = d.po_id
             WHERE d.po_id = ? AND d.warehouse_id = ? AND po.warehouse_id = ?
               AND d.doc_type IN ('supplier_invoice', 'money_transfer')
             ORDER BY CASE d.doc_type WHEN 'supplier_invoice' THEN 0 ELSE 1 END, d.id ASC",
            [$poId, $warehouseId, $warehouseId]
        ) ?: [];
        $sum = self::summarizeRows($files);
        $sum['cancelled'] = $cancelled;
        $sum['po_no'] = (string) ($po['po_no'] ?? '');
        $sum['po_date'] = (string) ($po['date'] ?? '');
        return $sum;
    }

    public static function url(string $token): string {
        if (function_exists('app_po_docs_verify_url')) {
            return app_po_docs_verify_url($token);
        }
        return rtrim(APP_URL, '/') . '/d/' . rawurlencode($token);
    }

    /**
     * @return array{verify_url:string,qr_png:?string}
     */
    public static function qrCoverFields(string $token): array {
        $url = $token === '' ? '' : self::url($token);
        return [
            'verify_url' => $url,
            'qr_png'     => $url !== '' ? self::qrPngDataUri($url) : null,
        ];
    }

    public static function qrPngDataUri(string $url): ?string {
        if ($url === '') {
            return null;
        }
        require_once __DIR__ . '/QrSvg.php';
        try {
            return QrSvg::pngDataUri($url, 168);
        } catch (Throwable $e) {
            error_log('[ERP] PO docs QR: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @param list<array<string,mixed>> $files
     * @return array{po_count:int,invoice_count:int,tt_count:int,fingerprint:string,rows:list<array{po_no:string,po_date:string,invoice_count:int,tt_count:int}>}
     */
    private static function summarizeRows(array $files): array {
        $groups = [];
        $ids = [];
        $inv = 0;
        $tt = 0;
        foreach ($files as $row) {
            $ids[] = (int) ($row['id'] ?? 0);
            $poId = (int) ($row['po_id'] ?? 0);
            if ($poId < 1) {
                continue;
            }
            if (!isset($groups[$poId])) {
                $groups[$poId] = [
                    'po_no'         => (string) ($row['po_no'] ?? ''),
                    'po_date'       => (string) ($row['po_date'] ?? ''),
                    'invoice_count' => 0,
                    'tt_count'      => 0,
                ];
            }
            if (($row['doc_type'] ?? '') === 'money_transfer') {
                $groups[$poId]['tt_count']++;
                $tt++;
            } else {
                $groups[$poId]['invoice_count']++;
                $inv++;
            }
        }
        return [
            'po_count'      => count($groups),
            'invoice_count' => $inv,
            'tt_count'      => $tt,
            'fingerprint'   => self::fingerprint($ids),
            'rows'          => array_values($groups),
        ];
    }

    private static function safeFp(string $fp): string {
        $fp = strtolower(preg_replace('/[^a-f0-9]/', '', $fp) ?? '');
        return strlen($fp) === 10 ? $fp : str_pad(substr($fp, 0, 10), 10, '0');
    }

    private static function ymd(string $key): ?string {
        if (strlen($key) !== 8 || !ctype_digit($key)) {
            return null;
        }
        $d = substr($key, 0, 4) . '-' . substr($key, 4, 2) . '-' . substr($key, 6, 2);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) ? $d : null;
    }

    private static function hmac(Database $db, string $body): string {
        $raw = hash_hmac('sha256', $body, self::secret($db), true);
        return rtrim(strtr(base64_encode(substr($raw, 0, 16)), '+/', '-_'), '=');
    }

    private static function secret(Database $db): string {
        $row = $db->fetchOne(
            'SELECT value FROM settings WHERE key_name = ?',
            [self::SETTING_KEY]
        );
        $existing = trim((string) ($row['value'] ?? ''));
        if ($existing !== '') {
            return $existing;
        }
        $generated = bin2hex(random_bytes(32));
        $db->execute(
            "INSERT INTO settings (key_name, value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE value = IF(value IS NULL OR value = '', VALUES(value), value)",
            [self::SETTING_KEY, $generated]
        );
        $again = $db->fetchOne(
            'SELECT value FROM settings WHERE key_name = ?',
            [self::SETTING_KEY]
        );
        $stored = trim((string) ($again['value'] ?? ''));
        return $stored !== '' ? $stored : $generated;
    }
}
