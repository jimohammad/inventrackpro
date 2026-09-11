<?php

/**
 * Signed public verification tokens for Manpower invoice packs.
 * Token binds warehouse + period + printed totals so a scan can be checked
 * against live sales (branch-scoped, cancelled excluded).
 */
class ManpowerVerify {

    public const SETTING_KEY = 'manpower_verify_secret';

    /**
     * Cover title for a printed pack (15 days / 30 days / 1 month / 3 months / custom).
     *
     * @return array{en:string,ar:string,sub_en:string,sub_ar:string}
     */
    public static function coverHeading(string $fromDate, string $toDate): array {
        require_once __DIR__ . '/ListPage.php';

        try {
            $from = new DateTimeImmutable($fromDate);
            $to   = new DateTimeImmutable($toDate);
        } catch (Exception $e) {
            return self::coverHeadingLabels('Sales Invoices', 'فواتير المبيعات', 'period');
        }

        $days  = (int) $from->diff($to)->days + 1;
        $today = ListPage::defaultToDate();

        if ($toDate === $today) {
            if ($fromDate === ListPage::defaultFromDateDays(15)) {
                return self::coverHeadingLabels('15 Days Sales Invoices', 'فواتير مبيعات 15 يوماً', '15_days');
            }
            if ($fromDate === ListPage::defaultFromDateDays(30)) {
                return self::coverHeadingLabels('30 Days Sales Invoices', 'فواتير مبيعات 30 يوماً', '30_days');
            }
            if ($fromDate === ListPage::defaultFromDate(1)) {
                return self::coverHeadingLabels('1 Month Sales Invoices', 'فواتير مبيعات شهر واحد', '1_month');
            }
            if ($fromDate === ListPage::defaultFromDate(3)) {
                return self::coverHeadingLabels('3 Months Sales Invoices', 'فواتير مبيعات 3 أشهر', '3_months');
            }
        }

        if ($days === 15) {
            return self::coverHeadingLabels('15 Days Sales Invoices', 'فواتير مبيعات 15 يوماً', '15_days');
        }
        if ($days === 30) {
            return self::coverHeadingLabels('30 Days Sales Invoices', 'فواتير مبيعات 30 يوماً', '30_days');
        }

        $fromFirst = $from->format('d') === '01';
        $monthSpan = ((int) $to->format('Y') * 12 + (int) $to->format('n'))
            - ((int) $from->format('Y') * 12 + (int) $from->format('n'))
            + 1;

        if ($fromFirst && $monthSpan === 1) {
            $monthEn = $from->format('F Y');
            return self::coverHeadingLabels($monthEn . ' Sales Invoices', 'فواتير مبيعات ' . $monthEn, 'calendar_month');
        }
        if ($fromFirst && $monthSpan === 3) {
            return self::coverHeadingLabels('3 Months Sales Invoices', 'فواتير مبيعات 3 أشهر', '3_months');
        }
        if ($fromFirst && $monthSpan > 1) {
            return self::coverHeadingLabels(
                $monthSpan . ' Months Sales Invoices',
                'فواتير مبيعات ' . $monthSpan . ' أشهر',
                'n_months'
            );
        }

        return self::coverHeadingLabels(
            $days . ' Days Sales Invoices',
            'فواتير مبيعات ' . $days . ' يوماً',
            'n_days'
        );
    }

    /**
     * @return array{en:string,ar:string,sub_en:string,sub_ar:string}
     */
    private static function coverHeadingLabels(string $en, string $ar, string $kind): array {
        $subs = [
            '15_days' => [
                'Certified copies of sales invoices for the last 15 days',
                'صور فواتير المبيعات الرسمية لآخر 15 يوماً',
            ],
            '30_days' => [
                'Certified copies of sales invoices for the last 30 days',
                'صور فواتير المبيعات الرسمية لآخر 30 يوماً',
            ],
            '1_month' => [
                'Certified copies of sales invoices for this month',
                'صور فواتير المبيعات الرسمية لهذا الشهر',
            ],
            '3_months' => [
                'Certified copies of sales invoices for the last 3 months',
                'صور فواتير المبيعات الرسمية لآخر 3 أشهر',
            ],
            'calendar_month' => [
                'Certified copies of sales invoices for this calendar month',
                'صور فواتير المبيعات الرسمية لهذا الشهر',
            ],
            'n_months' => [
                'Certified copies of sales invoices for this period',
                'صور فواتير المبيعات الرسمية لهذه الفترة',
            ],
            'n_days' => [
                'Certified copies of sales invoices for this period',
                'صور فواتير المبيعات الرسمية لهذه الفترة',
            ],
            'period' => [
                'Certified extracts from computerized sales records',
                'صور فواتير المبيعات الرسمية',
            ],
        ];
        $sub = $subs[$kind] ?? $subs['period'];
        return [
            'en'     => $en,
            'ar'     => $ar,
            'sub_en' => $sub[0],
            'sub_ar' => $sub[1],
        ];
    }

    /**
     * @return array{invoice_count:int,grand_total:float,invoices:list<array{invoice_no:string,date:string,grand_total:float}>}
     */
    public static function livePack(Database $db, int $warehouseId, string $fromDate, string $toDate): array {
        $rows = $db->fetchAll(
            "SELECT s.invoice_no, s.date, s.grand_total
             FROM sales s
             WHERE s.date BETWEEN ? AND ?
               AND s.warehouse_id = ?
               AND s.status != 'cancelled'
             ORDER BY s.date ASC, s.id ASC",
            [$fromDate, $toDate, $warehouseId]
        );

        $total = 0.0;
        $invoices = [];
        foreach ($rows as $row) {
            $amt = (float) ($row['grand_total'] ?? 0);
            $total += $amt;
            $invoices[] = [
                'invoice_no'  => (string) ($row['invoice_no'] ?? ''),
                'date'        => (string) ($row['date'] ?? ''),
                'grand_total' => $amt,
            ];
        }

        return [
            'invoice_count' => count($invoices),
            'grand_total'   => $total,
            'invoices'      => $invoices,
        ];
    }

    public static function sign(
        Database $db,
        int $warehouseId,
        string $fromDate,
        string $toDate,
        int $invoiceCount,
        float $grandTotal
    ): string {
        $fromKey = str_replace('-', '', $fromDate);
        $toKey   = str_replace('-', '', $toDate);
        $issued  = date('Ymd');
        $fils    = self::toFils($grandTotal);
        $body    = '1.' . $warehouseId . '.' . $fromKey . '.' . $toKey . '.' . $invoiceCount . '.' . $fils . '.' . $issued;
        $sig     = self::hmac($db, $body);
        return $body . '.' . $sig;
    }

    /**
     * @return array{
     *   warehouse_id:int,from_date:string,to_date:string,
     *   invoice_count:int,grand_total:float,issued:string
     * }|null
     */
    public static function parse(Database $db, string $token): ?array {
        $token = trim($token);
        if ($token === '' || strlen($token) > 160) {
            return null;
        }
        $parts = explode('.', $token);
        if (count($parts) !== 8 || $parts[0] !== '1') {
            return null;
        }
        [$ver, $wh, $fromKey, $toKey, $count, $fils, $issued, $sig] = $parts;
        unset($ver);
        if (!ctype_digit($wh) || !ctype_digit($fromKey) || !ctype_digit($toKey)
            || !ctype_digit($count) || !ctype_digit($fils) || !ctype_digit($issued)
            || strlen($fromKey) !== 8 || strlen($toKey) !== 8 || strlen($issued) !== 8
        ) {
            return null;
        }
        $body = $parts[0] . '.' . $wh . '.' . $fromKey . '.' . $toKey . '.' . $count . '.' . $fils . '.' . $issued;
        $expect = self::hmac($db, $body);
        if (!hash_equals($expect, $sig)) {
            return null;
        }
        $fromDate = substr($fromKey, 0, 4) . '-' . substr($fromKey, 4, 2) . '-' . substr($fromKey, 6, 2);
        $toDate   = substr($toKey, 0, 4) . '-' . substr($toKey, 4, 2) . '-' . substr($toKey, 6, 2);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
            return null;
        }
        if ($fromDate > $toDate) {
            return null;
        }

        return [
            'warehouse_id'  => (int) $wh,
            'from_date'     => $fromDate,
            'to_date'       => $toDate,
            'invoice_count' => (int) $count,
            'grand_total'   => ((int) $fils) / 1000,
            'issued'        => substr($issued, 0, 4) . '-' . substr($issued, 4, 2) . '-' . substr($issued, 6, 2),
        ];
    }

    public static function url(string $token): string {
        if (function_exists('app_manpower_verify_url')) {
            return app_manpower_verify_url($token);
        }
        return rtrim(APP_URL, '/') . '/v/' . rawurlencode($token);
    }

    private static function toFils(float $amount): int {
        return (int) round($amount * 1000);
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
