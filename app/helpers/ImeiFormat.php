<?php

/**
 * Phone IMEI (digits) vs tablet carton serial (alphanumeric, e.g. Samsung R8YL60BBJBD).
 */
final class ImeiFormat {
    public const KIND_PHONE  = 'phone';
    public const KIND_TABLET = 'tablet';

    public static function normalizeKind(string $kind): string {
        $kind = strtolower(trim($kind));
        return $kind === self::KIND_TABLET ? self::KIND_TABLET : self::KIND_PHONE;
    }

    public static function kindFromItem(?array $item, string $itemName = '', string $categoryName = ''): string {
        if (is_array($item) && self::normalizeKind((string) ($item['serial_kind'] ?? '')) === self::KIND_TABLET) {
            return self::KIND_TABLET;
        }
        $blob = strtolower($itemName . ' ' . $categoryName . ' ' . (string) ($item['name'] ?? '') . ' ' . (string) ($item['category_name'] ?? ''));
        if (str_contains($blob, 'tablet') || str_contains($blob, 'galaxy tab')) {
            return self::KIND_TABLET;
        }

        return self::KIND_PHONE;
    }

    /** Strip scanner noise; uppercase. */
    public static function normalize(string $raw): string {
        $s = strtoupper(trim($raw));
        $s = preg_replace('/[\r\n\t\s]+/', '', $s) ?? '';

        return preg_replace('/[^A-Z0-9]/', '', $s) ?? '';
    }

    public static function isValid(string $value, string $kind, string $itemName = ''): bool {
        return self::error($value, $kind, $itemName) === null;
    }

    /** True for a phone IMEI or a tablet serial (scan-first paste of mixed SKUs). */
    public static function isPlausible(string $value): bool {
        $v = self::normalize($value);
        if ($v === '') {
            return false;
        }
        if (self::isValid($v, self::KIND_TABLET)) {
            return true;
        }

        return self::isValid($v, self::KIND_PHONE, '');
    }

    public static function error(string $value, string $kind, string $itemName = ''): ?string {
        $v = self::normalize($value);
        if ($v === '') {
            return 'Serial is empty.';
        }

        if (self::normalizeKind($kind) === self::KIND_TABLET) {
            $len = strlen($v);
            if ($len < 11 || $len > 20) {
                return 'Tablet serial must be 11–20 letters/numbers (e.g. R8YL60BBJBD).';
            }
            if (!preg_match('/[A-Z]/', $v)) {
                return 'Tablet serial must include a letter (not a barcode/EAN).';
            }
            if (!preg_match('/^[A-Z0-9]{11,20}$/', $v)) {
                return 'Tablet serial may only use letters and numbers.';
            }

            return null;
        }

        if (!ctype_digit($v)) {
            return 'IMEI must contain digits only.';
        }
        $name = strtolower($itemName);
        $len  = strlen($v);
        if (str_contains($name, 'h40')) {
            return $len === 13 ? null : 'IMEI must be exactly 13 digits for H40.';
        }
        if ($len >= 15 && $len <= 18) {
            return null;
        }

        return 'IMEI must be 15–18 digits.';
    }
}
