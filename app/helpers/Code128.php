<?php

/**
 * Code 128 set B → inline SVG (no CDN).
 * Bar patterns follow the Code 128 specification (values 0–106).
 * Set B covers digits, letters, and other printable ASCII — IMEIs and tablet serials.
 */
class Code128 {

    /** @var list<string> */
    private const PATTERNS = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
        '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
        '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
        '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
        '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
        '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
        '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
        '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
        '114131', '311141', '411131', '211412', '211214', '211232', '2331112',
    ];

    public static function svg(string $text, int $barHeight = 46): ?string {
        $codes = self::encode(trim($text));
        if ($codes === null) {
            return null;
        }

        $barHeight = max(20, $barHeight);
        $quiet = 10;
        $x = $quiet;
        $rects = '';
        foreach ($codes as $code) {
            $pattern = self::PATTERNS[$code];
            $drawBar = true;
            $len = strlen($pattern);
            for ($i = 0; $i < $len; $i++) {
                $w = (int) $pattern[$i];
                if ($drawBar && $w > 0) {
                    $rects .= '<rect x="' . $x . '" y="0" width="' . $w . '" height="' . $barHeight . '"/>';
                }
                $x += $w;
                $drawBar = !$drawBar;
            }
        }
        $width = $x + $quiet;

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $width . ' ' . $barHeight
            . '" preserveAspectRatio="none" role="img" aria-label="Barcode">'
            . '<rect width="100%" height="100%" fill="#ffffff"/>'
            . '<g fill="#000000">' . $rects . '</g></svg>';
    }

    /**
     * Digits use set C (two digits per bar) so a 15-digit IMEI stays scannable at 4-up on A4.
     * Letters stay on set B.
     *
     * @return list<int>|null
     */
    private static function encode(string $text): ?array {
        if ($text === '' || strlen($text) > 80) {
            return null;
        }
        if (ctype_digit($text)) {
            return self::encodeDigits($text);
        }
        return self::encodeSetB($text);
    }

    /**
     * @return list<int>|null
     */
    private static function encodeDigits(string $text): ?array {
        $codes = [105];
        $sum = 105;
        $weight = 1;
        $len = strlen($text);
        $i = 0;
        while ($i + 1 < $len) {
            $value = (int) substr($text, $i, 2);
            $codes[] = $value;
            $sum += $value * $weight;
            $weight++;
            $i += 2;
        }
        if ($i < $len) {
            $codes[] = 100;
            $sum += 100 * $weight;
            $weight++;
            $value = ord($text[$i]) - 32;
            $codes[] = $value;
            $sum += $value * $weight;
        }
        $codes[] = $sum % 103;
        $codes[] = 106;
        return $codes;
    }

    /**
     * @return list<int>|null
     */
    private static function encodeSetB(string $text): ?array {
        $codes = [104];
        $sum = 104;
        $weight = 1;
        $len = strlen($text);
        for ($i = 0; $i < $len; $i++) {
            $value = ord($text[$i]) - 32;
            if ($value < 0 || $value > 94) {
                return null;
            }
            $codes[] = $value;
            $sum += $value * $weight;
            $weight++;
        }
        $codes[] = $sum % 103;
        $codes[] = 106;
        return $codes;
    }
}
