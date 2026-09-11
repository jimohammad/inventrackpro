<?php

require_once __DIR__ . '/../helpers/ImeiFormat.php';
require_once __DIR__ . '/../models/Item.php';

/**
 * Portable sales-invoice JSON for shop-to-shop file exchange.
 * Shop A exports a sale; shop B imports it as a purchase (money + stock + IMEIs).
 * This is not Intershop (stock-only, no invoice).
 */
final class SaleInvoiceFile {
    public const FORMAT  = 'iqbalerp.sale_invoice';
    public const VERSION = 1;

    /**
     * @param array<string, mixed> $sale Sale::findFull() row
     * @return array<string, mixed>
     */
    public static function buildFromSale(array $sale, Database $db): array {
        if (($sale['status'] ?? '') === 'cancelled') {
            throw new RuntimeException('Cancelled invoices cannot be exported.');
        }

        $rows = $sale['items'] ?? [];
        if (!is_array($rows) || $rows === []) {
            throw new RuntimeException('Invoice has no items.');
        }

        Item::ensureSerialKindColumn();
        $itemIds = array_values(array_unique(array_filter(array_map(
            static fn($r) => (int) ($r['item_id'] ?? 0),
            $rows
        ))));
        $meta = self::loadItemMeta($db, $itemIds);

        $outItems = [];
        foreach ($rows as $row) {
            $itemId = (int) ($row['item_id'] ?? 0);
            $info   = $meta[$itemId] ?? null;
            if ($info === null) {
                throw new RuntimeException('An invoice line points to a missing catalog item.');
            }

            $sku = strtoupper(trim((string) ($info['sku'] ?? '')));
            if ($sku === '') {
                throw new RuntimeException(
                    'Item "' . ($info['name'] ?? '') . '" has no SKU. Set the same SKU on both shops before exporting.'
                );
            }

            $qty = (int) ($row['quantity'] ?? 0);
            if ($qty <= 0) {
                throw new RuntimeException('Item "' . $info['name'] . '" has an invalid quantity.');
            }

            $imeis = [];
            foreach (explode('||', (string) ($row['imei_list'] ?? '')) as $raw) {
                $n = ImeiFormat::normalize($raw);
                if ($n !== '') {
                    $imeis[] = $n;
                }
            }
            $imeis   = array_values(array_unique($imeis));
            $hasImei = (int) ($info['has_imei'] ?? 0) === 1;
            if ($hasImei && count($imeis) !== $qty) {
                throw new RuntimeException(
                    'Item "' . $info['name'] . '": scan ' . $qty . ' serial(s) before export (have ' . count($imeis) . ').'
                );
            }
            if (!$hasImei && $imeis !== []) {
                throw new RuntimeException('Item "' . $info['name'] . '" is not serial-tracked but has IMEIs.');
            }

            $unitPrice = round((float) ($row['unit_price'] ?? 0), DECIMAL_PLACES);
            $discount  = round((float) ($row['discount'] ?? 0), DECIMAL_PLACES);
            $lineTotal = array_key_exists('total', $row)
                ? round((float) $row['total'], DECIMAL_PLACES)
                : round(($unitPrice * $qty) - $discount, DECIMAL_PLACES);

            $outItems[] = [
                'sku'         => $sku,
                'barcode'     => trim((string) ($info['barcode'] ?? '')),
                'name'        => (string) ($info['name'] ?? ''),
                'quantity'    => $qty,
                'unit_price'  => $unitPrice,
                'discount'    => $discount,
                'line_total'  => $lineTotal,
                'has_imei'    => $hasImei,
                'serial_kind' => ImeiFormat::normalizeKind((string) ($info['serial_kind'] ?? 'phone')),
                'imeis'       => $hasImei ? $imeis : [],
            ];
        }

        $host     = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $fromShop = $host !== '' ? $host : APP_NAME;

        return [
            'format'      => self::FORMAT,
            'version'     => self::VERSION,
            'exported_at' => date('c'),
            'from_shop'   => $fromShop,
            'invoice'     => [
                'invoice_no'  => (string) ($sale['invoice_no'] ?? ''),
                'date'        => (string) ($sale['date'] ?? ''),
                'currency'    => APP_CURRENCY,
                'subtotal'    => round((float) ($sale['subtotal'] ?? 0), DECIMAL_PLACES),
                'discount'    => round((float) ($sale['discount'] ?? 0), DECIMAL_PLACES),
                'grand_total' => round((float) ($sale['grand_total'] ?? 0), DECIMAL_PLACES),
                'party_name'  => (string) ($sale['party_name'] ?? ''),
            ],
            'items'       => $outItems,
        ];
    }

    /** @param array<string, mixed> $payload */
    public static function filename(array $payload): string {
        $no   = (string) ($payload['invoice']['invoice_no'] ?? 'invoice');
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', $no) ?: 'invoice';

        return $safe . '.iqbal.json';
    }

    public static function encode(array $payload): string {
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('Could not encode invoice file.');
        }

        return $json;
    }

    /** @return array<string, mixed> */
    public static function parse(string $raw): array {
        $raw = trim($raw);
        if ($raw === '') {
            throw new RuntimeException('File is empty.');
        }
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            $raw = substr($raw, 3);
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new RuntimeException('Not a valid JSON invoice file.');
        }
        if (($data['format'] ?? '') !== self::FORMAT) {
            throw new RuntimeException('This file is not an Iqbalerp sales invoice export.');
        }
        if ((int) ($data['version'] ?? 0) !== self::VERSION) {
            throw new RuntimeException('Unsupported invoice file version.');
        }

        $invoice = $data['invoice'] ?? null;
        $items   = $data['items'] ?? null;
        if (!is_array($invoice) || !is_array($items) || $items === []) {
            throw new RuntimeException('Invoice file is missing header or items.');
        }
        if (trim((string) ($invoice['invoice_no'] ?? '')) === '') {
            throw new RuntimeException('Invoice file has no invoice number.');
        }

        return $data;
    }

    /**
     * Match file lines to this shop’s catalog (SKU first, barcode fallback).
     * Does not create items.
     *
     * @param array<string, mixed> $payload
     * @return array{
     *   ok: bool,
     *   errors: list<string>,
     *   invoice: array<string, mixed>,
     *   from_shop: string,
     *   lines: list<array<string, mixed>>,
     *   subtotal: float,
     *   discount: float,
     *   grand_total: float,
     *   source_invoice_no: string
     * }
     */
    public static function resolveForWarehouse(array $payload, Database $db): array {
        Item::ensureSerialKindColumn();

        $invoice  = is_array($payload['invoice'] ?? null) ? $payload['invoice'] : [];
        $fromShop = trim((string) ($payload['from_shop'] ?? ''));
        $errors   = [];
        $lines    = [];
        $seenImei = [];

        foreach ($payload['items'] ?? [] as $idx => $row) {
            $n = (int) $idx + 1;
            if (!is_array($row)) {
                $errors[] = 'Line ' . $n . ' is invalid.';
                continue;
            }

            $sku     = strtoupper(trim((string) ($row['sku'] ?? '')));
            $barcode = trim((string) ($row['barcode'] ?? ''));
            $name    = trim((string) ($row['name'] ?? ''));
            $qty     = (int) ($row['quantity'] ?? 0);
            $label   = $sku !== '' ? $sku : ($name !== '' ? $name : ('line ' . $n));

            $imeis = [];
            foreach ((array) ($row['imeis'] ?? []) as $raw) {
                $imei = ImeiFormat::normalize((string) $raw);
                if ($imei === '') {
                    continue;
                }
                if (isset($seenImei[$imei])) {
                    $errors[] = $label . ': duplicate IMEI ' . $imei . ' in this file.';
                    continue;
                }
                $seenImei[$imei] = true;
                $imeis[]         = $imei;
            }

            $lineTotal = array_key_exists('line_total', $row)
                ? (float) $row['line_total']
                : (((float) ($row['unit_price'] ?? 0) * max($qty, 0)) - (float) ($row['discount'] ?? 0));
            $lineTotal = round($lineTotal, DECIMAL_PLACES);
            $netUnit   = $qty > 0 ? round($lineTotal / $qty, DECIMAL_PLACES) : 0.0;

            if ($qty <= 0) {
                $msg      = $label . ': quantity must be greater than zero.';
                $errors[] = $msg;
                $lines[]  = self::unmatchedLine($row, $imeis, $qty, $netUnit, $lineTotal, $msg);
                continue;
            }
            if ($lineTotal < 0) {
                $errors[] = $label . ': line total cannot be negative.';
            }

            try {
                $local = self::matchItem($db, $sku, $barcode);
            } catch (RuntimeException $e) {
                $errors[] = $e->getMessage();
                $lines[]  = self::unmatchedLine($row, $imeis, $qty, $netUnit, $lineTotal, $e->getMessage());
                continue;
            }

            if ($local === null) {
                $msg      = $sku !== ''
                    ? 'SKU "' . $sku . '" not found on this shop. Create the item with the same SKU first.'
                    : 'No matching item (SKU/barcode) for "' . $label . '".';
                $errors[] = $msg;
                $lines[]  = self::unmatchedLine($row, $imeis, $qty, $netUnit, $lineTotal, $msg);
                continue;
            }

            $localHasImei = (int) ($local['has_imei'] ?? 0) === 1;
            $kind         = ImeiFormat::normalizeKind((string) ($local['serial_kind'] ?? 'phone'));
            $localName    = (string) ($local['name'] ?? $label);

            if ($localHasImei) {
                if (count($imeis) !== $qty) {
                    $errors[] = $localName . ': need ' . $qty . ' serial(s), file has ' . count($imeis) . '.';
                }
                foreach ($imeis as $imei) {
                    $fmt = ImeiFormat::error($imei, $kind, $localName);
                    if ($fmt !== null) {
                        $errors[] = $localName . ': ' . $fmt . ' (' . $imei . ')';
                    }
                }
            } elseif ($imeis !== []) {
                $errors[] = $localName . ' is not serial-tracked on this shop; the file has IMEIs.';
            }

            $lines[] = [
                'matched'    => true,
                'item_id'    => (int) $local['id'],
                'local_name' => $localName,
                'sku'        => (string) ($local['sku'] ?? $sku),
                'file_name'  => $name,
                'quantity'   => $qty,
                'unit_price' => $netUnit,
                'line_total' => $lineTotal,
                'has_imei'   => $localHasImei,
                'imei_count' => count($imeis),
                'imeis'      => $localHasImei ? $imeis : [],
                'error'      => null,
            ];
        }

        $headerSub   = round((float) ($invoice['subtotal'] ?? 0), DECIMAL_PLACES);
        $headerDisc  = round((float) ($invoice['discount'] ?? 0), DECIMAL_PLACES);
        $headerGrand = round((float) ($invoice['grand_total'] ?? 0), DECIMAL_PLACES);

        $sumLines = 0.0;
        foreach ($lines as $ln) {
            if (!empty($ln['matched'])) {
                $sumLines += (float) $ln['line_total'];
            }
        }
        $sumLines = round($sumLines, DECIMAL_PLACES);
        if ($headerSub <= 0 && $sumLines > 0) {
            $headerSub = $sumLines;
        }
        if ($headerGrand <= 0) {
            $headerGrand = round($headerSub - $headerDisc, DECIMAL_PLACES);
        }
        if ($headerDisc < -0.0005 || $headerDisc > $headerSub + 0.001) {
            $errors[] = 'Invoice discount is invalid.';
        }
        if ($headerGrand < -0.0005) {
            $errors[] = 'Grand total cannot be negative.';
        }

        $ok = $errors === [] && $lines !== [];
        foreach ($lines as $ln) {
            if (empty($ln['matched'])) {
                $ok = false;
                break;
            }
        }

        return [
            'ok'                 => $ok,
            'errors'             => $errors,
            'invoice'            => $invoice,
            'from_shop'          => $fromShop,
            'lines'              => $lines,
            'subtotal'           => $headerSub,
            'discount'           => $headerDisc,
            'grand_total'        => $headerGrand,
            'source_invoice_no'  => trim((string) ($invoice['invoice_no'] ?? '')),
        ];
    }

    /**
     * @param array<string, mixed> $resolved
     * @return list<array{item_id:int,quantity:int,unit_price:float,imeis:list<string>}>
     */
    public static function toPurchaseItems(array $resolved): array {
        $items = [];
        foreach ($resolved['lines'] ?? [] as $line) {
            if (empty($line['matched'])) {
                continue;
            }
            $items[] = [
                'item_id'    => (int) $line['item_id'],
                'quantity'   => (int) $line['quantity'],
                'unit_price' => (float) $line['unit_price'],
                'imeis'      => array_values((array) ($line['imeis'] ?? [])),
            ];
        }

        return $items;
    }

    public static function importNotes(array $resolved): string {
        $shop = trim((string) ($resolved['from_shop'] ?? ''));
        $no   = trim((string) ($resolved['source_invoice_no'] ?? ''));
        $from = trim($shop . ' ' . $no);

        return $from !== '' ? ('Imported from ' . $from) : 'Imported sales invoice file';
    }

    /**
     * @param list<int> $itemIds
     * @return array<int, array<string, mixed>>
     */
    private static function loadItemMeta(Database $db, array $itemIds): array {
        if ($itemIds === []) {
            return [];
        }
        $ph   = implode(',', array_fill(0, count($itemIds), '?'));
        $rows = $db->fetchAll(
            "SELECT id, name, sku, barcode, has_imei, COALESCE(serial_kind, 'phone') AS serial_kind
             FROM items
             WHERE id IN ({$ph})",
            $itemIds
        );
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['id']] = $r;
        }

        return $map;
    }

    /**
     * Same uniqueness rules as IntershopTransfer::matchItem — SKU first, barcode fallback.
     *
     * @return array<string, mixed>|null
     */
    private static function matchItem(Database $db, string $sku, string $barcode): ?array {
        if ($sku !== '') {
            $rows = $db->fetchAll(
                "SELECT id, name, sku, has_imei, COALESCE(serial_kind,'phone') AS serial_kind
                 FROM items
                 WHERE is_active = 1 AND sku IS NOT NULL AND TRIM(sku) != ''
                   AND UPPER(TRIM(sku)) = ?",
                [$sku]
            );
            if (count($rows) === 1) {
                return $rows[0];
            }
            if (count($rows) > 1) {
                throw new RuntimeException('SKU "' . $sku . '" matches more than one item on this shop.');
            }
        }

        $barcode = trim($barcode);
        if ($barcode !== '') {
            $rows = $db->fetchAll(
                "SELECT id, name, sku, has_imei, COALESCE(serial_kind,'phone') AS serial_kind
                 FROM items
                 WHERE is_active = 1 AND barcode IS NOT NULL AND TRIM(barcode) != ''
                   AND TRIM(barcode) = ?",
                [$barcode]
            );
            if (count($rows) === 1) {
                return $rows[0];
            }
            if (count($rows) > 1) {
                throw new RuntimeException('Barcode "' . $barcode . '" matches more than one item on this shop.');
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     * @param list<string> $imeis
     * @return array<string, mixed>
     */
    private static function unmatchedLine(
        array $row,
        array $imeis,
        int $qty,
        float $netUnit,
        float $lineTotal,
        string $error
    ): array {
        return [
            'matched'    => false,
            'item_id'    => 0,
            'local_name' => '',
            'sku'        => strtoupper(trim((string) ($row['sku'] ?? ''))),
            'file_name'  => trim((string) ($row['name'] ?? '')),
            'quantity'   => $qty,
            'unit_price' => $netUnit,
            'line_total' => $lineTotal,
            'has_imei'   => !empty($row['has_imei']),
            'imei_count' => count($imeis),
            'imeis'      => $imeis,
            'error'      => $error,
        ];
    }
}
