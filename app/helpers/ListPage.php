<?php

/**
 * Shared list-page date defaults and row-cap detection for heavy index tables.
 */
class ListPage {
    public const MAX_ROWS = 500;

    /** Max ledger lines rendered on statement reports (DOM + DataTables). */
    public const REPORT_LEDGER_MAX = 500;

    /** Refuse to process more than this many ledger lines server-side. */
    public const REPORT_LEDGER_PROCESS_MAX = 3000;

    /** Maximum inclusive span for statement date ranges. */
    public const MAX_REPORT_DAYS = 366;

    public static function defaultFromDate(): string {
        return date('Y-m-01');
    }

    public static function defaultToDate(): string {
        return date('Y-m-d');
    }

    /**
     * Resolve from/to dates for sales, payments, purchases list pages.
     *
     * - First visit (no from_date / to_date in query): current calendar month through today.
     * - all_dates=1: no date filter (still capped at MAX_ROWS).
     * - Filter form with both dates empty: treated as all_dates.
     *
     * @return array{from_date:string,to_date:string,all_dates:bool,dates_defaulted:bool}
     */
    public static function resolveDateFiltersFromGet(): array {
        $get      = $_GET;
        $allDates = isset($get['all_dates']) && (string) $get['all_dates'] === '1';

        if ($allDates) {
            return [
                'from_date'       => '',
                'to_date'         => '',
                'all_dates'       => true,
                'dates_defaulted' => false,
            ];
        }

        $fromExplicit = array_key_exists('from_date', $get);
        $toExplicit   = array_key_exists('to_date', $get);

        if (!$fromExplicit && !$toExplicit) {
            return [
                'from_date'       => self::defaultFromDate(),
                'to_date'         => self::defaultToDate(),
                'all_dates'       => false,
                'dates_defaulted' => true,
            ];
        }

        $from = trim((string) ($get['from_date'] ?? ''));
        $to   = trim((string) ($get['to_date'] ?? ''));

        if ($from === '' && $to === '') {
            return [
                'from_date'       => '',
                'to_date'         => '',
                'all_dates'       => true,
                'dates_defaulted' => false,
            ];
        }

        if ($to === '') {
            $to = self::defaultToDate();
        }
        if ($from === '') {
            $from = self::defaultFromDate();
        }

        return [
            'from_date'       => $from,
            'to_date'         => $to,
            'all_dates'       => false,
            'dates_defaulted' => false,
        ];
    }

    /** Dates safe for summary queries when list uses all_dates. */
    public static function summaryDateRange(array $filters): array {
        if (!empty($filters['all_dates']) || (($filters['from_date'] ?? '') === '' && ($filters['to_date'] ?? '') === '')) {
            return [self::defaultFromDate(), self::defaultToDate()];
        }
        $from = (string) ($filters['from_date'] ?? self::defaultFromDate());
        $to   = (string) ($filters['to_date'] ?? self::defaultToDate());
        return [$from, $to];
    }

    /**
     * @param list<array<string,mixed>> $rows
     * @return array{items:list<array<string,mixed>>,truncated:bool,limit:int}
     */
    public static function capRows(array $rows, int $limit = self::MAX_ROWS): array {
        $limit = max(1, min(self::MAX_ROWS, $limit));
        $truncated = count($rows) > $limit;
        if ($truncated) {
            $rows = array_slice($rows, 0, $limit);
        }
        return [
            'items'     => $rows,
            'truncated' => $truncated,
            'limit'     => $limit,
        ];
    }

    /**
     * @param array<string,scalar|null> $extra
     */
    public static function allDatesUrl(string $page, array $extra = []): string {
        $params = array_merge(['page' => $page, 'all_dates' => '1'], $extra);
        return '?' . http_build_query($params);
    }

    public static function validateReportDateRange(string $fromDate, string $toDate, int $maxDays = self::MAX_REPORT_DAYS): ?string {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
            return 'Please select valid from and to dates.';
        }
        if ($fromDate > $toDate) {
            return 'From date must be on or before to date.';
        }
        $from = new DateTimeImmutable($fromDate);
        $to   = new DateTimeImmutable($toDate);
        if ((int) $from->diff($to)->days > $maxDays) {
            return 'Date range cannot exceed ' . $maxDays . ' days. Please choose a shorter period.';
        }
        return null;
    }

    /**
     * Cap ledger rows for report HTML; reject ranges that are too large to process.
     *
     * @param list<array<string,mixed>> $rows
     * @return array{
     *   error: string|null,
     *   all: list<array<string,mixed>>,
     *   display: list<array<string,mixed>>,
     *   truncated: bool,
     *   limit: int,
     *   total_count: int
     * }
     */
    public static function prepareLedgerDisplay(array $rows): array {
        $total = count($rows);
        if ($total > self::REPORT_LEDGER_PROCESS_MAX) {
            return [
                'error'       => 'Too many transactions (' . number_format($total) . ') for this range. Maximum is '
                    . number_format(self::REPORT_LEDGER_PROCESS_MAX) . '. Please narrow the date range.',
                'all'         => [],
                'display'     => [],
                'truncated'   => false,
                'limit'       => self::REPORT_LEDGER_MAX,
                'total_count' => $total,
            ];
        }

        $capped = self::capRows($rows, self::REPORT_LEDGER_MAX);

        return [
            'error'       => null,
            'all'         => $rows,
            'display'     => $capped['items'],
            'truncated'   => $capped['truncated'],
            'limit'       => $capped['limit'],
            'total_count' => $total,
        ];
    }
}
