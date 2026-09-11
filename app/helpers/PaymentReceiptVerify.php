<?php

/**
 * Signed public verification for a single payment receipt QR.
 * Token binds payment id + warehouse + printed amount so a scan can be
 * checked against the live row (no login).
 */
class PaymentReceiptVerify
{
    public const SETTING_KEY = 'payment_receipt_verify_secret';

    /**
     * @param array<string,mixed> $payment
     */
    public static function sign(Database $db, array $payment): string
    {
        $id = (int) ($payment['id'] ?? 0);
        if ($id <= 0) {
            throw new InvalidArgumentException('Payment id is required for receipt QR.');
        }
        $wh = self::warehouseId($payment);
        $fils = self::toFils((float) ($payment['amount'] ?? 0));
        $issued = date('Ymd');
        $body = 'r1.' . $id . '.' . $wh . '.' . $fils . '.' . $issued;

        return $body . '.' . self::hmac($db, $body);
    }

    /**
     * @return array{payment_id:int,warehouse_id:int,amount:float,issued:string}|null
     */
    public static function parse(Database $db, string $token): ?array
    {
        $token = trim($token);
        if ($token === '' || strlen($token) > 120) {
            return null;
        }
        $parts = explode('.', $token);
        if (count($parts) !== 6 || $parts[0] !== 'r1') {
            return null;
        }
        [$ver, $id, $wh, $fils, $issued, $sig] = $parts;
        unset($ver);
        if (!ctype_digit($id) || !ctype_digit($wh) || !ctype_digit($fils) || !ctype_digit($issued)
            || strlen($issued) !== 8
        ) {
            return null;
        }
        $body = 'r1.' . $id . '.' . $wh . '.' . $fils . '.' . $issued;
        if (!hash_equals(self::hmac($db, $body), $sig)) {
            return null;
        }

        return [
            'payment_id'   => (int) $id,
            'warehouse_id' => (int) $wh,
            'amount'       => ((int) $fils) / 1000,
            'issued'       => substr($issued, 0, 4) . '-' . substr($issued, 4, 2) . '-' . substr($issued, 6, 2),
        ];
    }

    /**
     * @return array{
     *   id:int,payment_no:string,date:string,amount:float,payment_type:string,
     *   status:string,warehouse_id:int,party_name:string
     * }|null
     */
    public static function live(Database $db, int $paymentId, int $warehouseId): ?array
    {
        $row = $db->fetchOne(
            "SELECT py.id, py.payment_no, py.date, py.amount, py.payment_type, py.status, py.warehouse_id,
                    pa.name AS party_name
             FROM payments py
             LEFT JOIN parties pa ON pa.id = py.party_id
             WHERE py.id = ?",
            [$paymentId]
        );
        if (!is_array($row)) {
            return null;
        }
        $rowWh = (int) ($row['warehouse_id'] ?? 0);
        if ($rowWh <= 0) {
            $rowWh = 1;
        }
        if ($rowWh !== $warehouseId) {
            return null;
        }

        return [
            'id'            => (int) $row['id'],
            'payment_no'   => (string) ($row['payment_no'] ?? ''),
            'date'         => (string) ($row['date'] ?? ''),
            'amount'       => (float) ($row['amount'] ?? 0),
            'payment_type' => (string) ($row['payment_type'] ?? 'in'),
            'status'       => (string) ($row['status'] ?? ''),
            'warehouse_id' => $rowWh,
            'party_name'   => (string) ($row['party_name'] ?? ''),
        ];
    }

    public static function url(string $token): string
    {
        if (function_exists('app_payment_verify_url')) {
            return app_payment_verify_url($token);
        }

        return rtrim(APP_URL, '/') . '/r/' . rawurlencode($token);
    }

    /**
     * @param array<string,mixed> $payment
     */
    public static function qrPngDataUri(Database $db, array $payment): ?string
    {
        try {
            $url = self::url(self::sign($db, $payment));
            require_once __DIR__ . '/QrSvg.php';

            return QrSvg::pngDataUri($url, 168);
        } catch (Throwable $e) {
            error_log('[ERP] Payment receipt QR: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * @param array<string,mixed> $payment
     */
    private static function warehouseId(array $payment): int
    {
        $wh = (int) ($payment['warehouse_id'] ?? 0);

        return $wh > 0 ? $wh : 1;
    }

    private static function toFils(float $amount): int
    {
        return (int) round($amount * 1000);
    }

    private static function hmac(Database $db, string $body): string
    {
        $raw = hash_hmac('sha256', $body, self::secret($db), true);

        return rtrim(strtr(base64_encode(substr($raw, 0, 16)), '+/', '-_'), '=');
    }

    private static function secret(Database $db): string
    {
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
