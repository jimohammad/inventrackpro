<?php

/**
 * Byte-mode QR Code (Model 2) → SVG data URI.
 * Algorithm follows Project Nayuki (MIT): https://www.nayuki.io/page/qr-code-generator-library
 *
 * Used so Manpower print/PDF does not wait on cdnjs qrcode.js.
 */
class QrSvg {

    public static function dataUri(string $text, int $sizePx = 132): string {
        $svg = self::svg($text, $sizePx);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * PNG data URI for pdf-lib (browser pack) — GD, Hostinger-safe.
     */
    public static function pngDataUri(string $text, int $sizePx = 168): string {
        if (!function_exists('imagecreatetruecolor') || !function_exists('imagepng')) {
            throw new RuntimeException('GD PNG support is not available.');
        }
        $modules = self::encodeModules($text);
        $n = count($modules);
        if ($n < 21) {
            throw new RuntimeException('QR encode failed.');
        }
        $quiet = 4;
        $dim = $n + (2 * $quiet);
        $sizePx = max(96, min(280, $sizePx));
        $scale = max(2, (int) floor($sizePx / $dim));
        $px = $dim * $scale;
        $im = imagecreatetruecolor($px, $px);
        if ($im === false) {
            throw new RuntimeException('Could not create QR image.');
        }
        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 0, 0, 0);
        if ($white === false || $black === false) {
            imagedestroy($im);
            throw new RuntimeException('Could not allocate QR colors.');
        }
        imagefilledrectangle($im, 0, 0, $px - 1, $px - 1, $white);
        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                if (empty($modules[$y][$x])) {
                    continue;
                }
                $x0 = ($x + $quiet) * $scale;
                $y0 = ($y + $quiet) * $scale;
                imagefilledrectangle($im, $x0, $y0, $x0 + $scale - 1, $y0 + $scale - 1, $black);
            }
        }
        ob_start();
        $ok = imagepng($im, null, 6);
        $bin = (string) ob_get_clean();
        imagedestroy($im);
        if (!$ok || $bin === '') {
            throw new RuntimeException('Could not encode QR PNG.');
        }
        return 'data:image/png;base64,' . base64_encode($bin);
    }

    /**
     * One SVG group so cover + invoice copies can <use> it (no extra HTTP, no per-page payload).
     * Finders/alignment stay square so scanners lock on; data modules are dots.
     *
     * @return array{id:string,dim:int,group:string}
     */
    public static function sprite(string $text, string $id = 'manpower-verify-qr'): array {
        $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $id) ?: 'manpower-verify-qr';
        $modules = self::encodeModules($text);
        $n = count($modules);
        $quiet = 4;
        $dim = $n + (2 * $quiet);
        $version = (int) (($n - 17) / 4);
        $alignCenters = self::alignmentCenters($version, $n);

        $dark = [];
        $light = [];
        $inner = [];
        $dots = [];

        foreach (self::finderOrigins($n) as [$ox, $oy]) {
            $x = $ox + $quiet;
            $y = $oy + $quiet;
            $dark[] = '<rect x="' . $x . '" y="' . $y . '" width="7" height="7" rx="0.55"/>';
            $light[] = '<rect x="' . ($x + 1) . '" y="' . ($y + 1) . '" width="5" height="5" rx="0.35"/>';
            $inner[] = '<rect x="' . ($x + 2) . '" y="' . ($y + 2) . '" width="3" height="3" rx="0.2"/>';
        }
        foreach ($alignCenters as [$cx, $cy]) {
            $x = $cx - 2 + $quiet;
            $y = $cy - 2 + $quiet;
            $dark[] = '<rect x="' . $x . '" y="' . $y . '" width="5" height="5" rx="0.4"/>';
            $light[] = '<rect x="' . ($x + 1) . '" y="' . ($y + 1) . '" width="3" height="3" rx="0.22"/>';
            $inner[] = '<rect x="' . ($cx + $quiet) . '" y="' . ($cy + $quiet) . '" width="1" height="1" rx="0.12"/>';
        }

        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                if (!$modules[$y][$x] || self::isSquarePatternCell($x, $y, $n, $alignCenters)) {
                    continue;
                }
                $dots[] = '<circle cx="' . ($x + $quiet + 0.5) . '" cy="' . ($y + $quiet + 0.5) . '" r="0.40"/>';
            }
        }

        $group = '<g id="' . $id . '">'
            . '<g fill="#0b1f36">' . implode('', $dark) . implode('', $dots) . '</g>'
            . '<g fill="#ffffff">' . implode('', $light) . '</g>'
            . '<g fill="#0b1f36">' . implode('', $inner) . '</g>'
            . '</g>';

        return [
            'id'    => $id,
            'dim'   => $dim,
            'group' => $group,
        ];
    }

    /** @return list<array{0:int,1:int}> */
    private static function finderOrigins(int $n): array {
        return [[0, 0], [$n - 7, 0], [0, $n - 7]];
    }

    /**
     * @return list<array{0:int,1:int}>
     */
    private static function alignmentCenters(int $version, int $n): array {
        $pos = self::alignmentPositions($version, $n);
        $num = count($pos);
        $centers = [];
        for ($i = 0; $i < $num; $i++) {
            for ($j = 0; $j < $num; $j++) {
                if (($i === 0 && $j === 0)
                    || ($i === 0 && $j === $num - 1)
                    || ($i === $num - 1 && $j === 0)
                ) {
                    continue;
                }
                $centers[] = [$pos[$i], $pos[$j]];
            }
        }
        return $centers;
    }

    /**
     * @param list<array{0:int,1:int}> $alignCenters
     */
    private static function isSquarePatternCell(int $x, int $y, int $n, array $alignCenters): bool {
        foreach (self::finderOrigins($n) as [$ox, $oy]) {
            if ($x >= $ox && $x < $ox + 7 && $y >= $oy && $y < $oy + 7) {
                return true;
            }
        }
        foreach ($alignCenters as [$cx, $cy]) {
            if (abs($x - $cx) <= 2 && abs($y - $cy) <= 2) {
                return true;
            }
        }
        return false;
    }

    public static function svg(string $text, int $sizePx = 132): string {
        $sprite = self::sprite($text);
        $sizePx = max(64, min(256, $sizePx));
        $dim = (int) $sprite['dim'];
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $dim . ' ' . $dim
            . '" width="' . $sizePx . '" height="' . $sizePx . '">'
            . '<rect width="100%" height="100%" fill="#ffffff"/>'
            . $sprite['group'] . '</svg>';
    }

    /** @return list<list<bool>> */
    public static function encodeModules(string $text): array {
        $bytes = array_values(unpack('C*', $text) ?: []);
        $segBits = [];
        foreach ($bytes as $b) {
            self::appendBits($segBits, $b, 8);
        }
        $numChars = count($bytes);
        $ecl = 1; // Medium (~15%), matches previous qrcode.js CorrectLevel.M

        $version = 0;
        for ($v = 1; $v <= 14; $v++) {
            $ccbits = ($v <= 9) ? 8 : 16;
            if ($numChars >= (1 << $ccbits)) {
                continue;
            }
            $used = 4 + $ccbits + count($segBits);
            $cap = self::numDataCodewords($v, $ecl) * 8;
            if ($used <= $cap) {
                $version = $v;
                break;
            }
        }
        if ($version === 0) {
            throw new RuntimeException('QR payload is too long.');
        }

        $ccbits = ($version <= 9) ? 8 : 16;
        $bb = [];
        self::appendBits($bb, 0x4, 4);
        self::appendBits($bb, $numChars, $ccbits);
        foreach ($segBits as $bit) {
            $bb[] = $bit;
        }

        $cap = self::numDataCodewords($version, $ecl) * 8;
        $term = min(4, $cap - count($bb));
        if ($term > 0) {
            self::appendBits($bb, 0, $term);
        }
        $padBits = (8 - (count($bb) % 8)) % 8;
        if ($padBits > 0) {
            self::appendBits($bb, 0, $padBits);
        }
        $pads = [0xEC, 0x11];
        $pi = 0;
        while (count($bb) < $cap) {
            self::appendBits($bb, $pads[$pi & 1], 8);
            $pi++;
        }

        $data = array_fill(0, intdiv(count($bb), 8), 0);
        foreach ($bb as $i => $bit) {
            $data[$i >> 3] |= $bit << (7 - ($i & 7));
        }

        return self::buildModules($version, $ecl, $data);
    }

    /**
     * @param list<int> $data
     * @return list<list<bool>>
     */
    private static function buildModules(int $version, int $ecl, array $data): array {
        $size = $version * 4 + 17;
        $modules = array_fill(0, $size, array_fill(0, $size, false));
        $isFn = array_fill(0, $size, array_fill(0, $size, false));

        self::drawFunctionPatterns($modules, $isFn, $version, $ecl, 0);
        $all = self::addEccAndInterleave($version, $ecl, $data);
        self::drawCodewords($modules, $isFn, $all);

        $bestMask = 0;
        $bestPenalty = PHP_INT_MAX;
        for ($mask = 0; $mask < 8; $mask++) {
            self::applyMask($modules, $isFn, $mask);
            self::drawFormatBits($modules, $isFn, $ecl, $mask, $size);
            $penalty = self::penaltyScore($modules, $size);
            if ($penalty < $bestPenalty) {
                $bestMask = $mask;
                $bestPenalty = $penalty;
            }
            self::applyMask($modules, $isFn, $mask);
        }
        self::applyMask($modules, $isFn, $bestMask);
        self::drawFormatBits($modules, $isFn, $ecl, $bestMask, $size);
        return $modules;
    }

    /** @param list<int> $bb */
    private static function appendBits(array &$bb, int $val, int $n): void {
        for ($i = $n - 1; $i >= 0; $i--) {
            $bb[] = ($val >> $i) & 1;
        }
    }

    private static function numRawDataModules(int $ver): int {
        $result = (16 * $ver + 128) * $ver + 64;
        if ($ver >= 2) {
            $numAlign = intdiv($ver, 7) + 2;
            $result -= (25 * $numAlign - 10) * $numAlign - 55;
        }
        if ($ver >= 7) {
            $result -= 36;
        }
        return $result;
    }

    private static function numDataCodewords(int $ver, int $ecl): int {
        return intdiv(self::numRawDataModules($ver), 8)
            - self::ECC_PER_BLOCK[$ecl][$ver] * self::ECC_BLOCKS[$ecl][$ver];
    }

    /** @return list<int> */
    private static function alignmentPositions(int $version, int $size): array {
        if ($version === 1) {
            return [];
        }
        $numAlign = intdiv($version, 7) + 2;
        $step = intdiv($version * 8 + $numAlign * 3 + 5, $numAlign * 4 - 4) * 2;
        $result = [];
        for ($i = 0; $i < $numAlign - 1; $i++) {
            $result[] = $size - 7 - $i * $step;
        }
        $result[] = 6;
        return array_reverse($result);
    }

    /**
     * @param list<list<bool>> $modules
     * @param list<list<bool>> $isFn
     */
    private static function drawFunctionPatterns(array &$modules, array &$isFn, int $version, int $ecl, int $mask): void {
        $size = count($modules);
        for ($i = 0; $i < $size; $i++) {
            self::setFn($modules, $isFn, 6, $i, ($i % 2) === 0);
            self::setFn($modules, $isFn, $i, 6, ($i % 2) === 0);
        }
        self::drawFinder($modules, $isFn, 3, 3, $size);
        self::drawFinder($modules, $isFn, $size - 4, 3, $size);
        self::drawFinder($modules, $isFn, 3, $size - 4, $size);

        $align = self::alignmentPositions($version, $size);
        $numAlign = count($align);
        for ($i = 0; $i < $numAlign; $i++) {
            for ($j = 0; $j < $numAlign; $j++) {
                if (($i === 0 && $j === 0)
                    || ($i === 0 && $j === $numAlign - 1)
                    || ($i === $numAlign - 1 && $j === 0)
                ) {
                    continue;
                }
                self::drawAlignment($modules, $isFn, $align[$i], $align[$j]);
            }
        }
        self::drawFormatBits($modules, $isFn, $ecl, $mask, $size);
        self::drawVersion($modules, $isFn, $version, $size);
    }

    /**
     * @param list<list<bool>> $modules
     * @param list<list<bool>> $isFn
     */
    private static function drawFinder(array &$modules, array &$isFn, int $cx, int $cy, int $size): void {
        for ($dy = -4; $dy <= 4; $dy++) {
            for ($dx = -4; $dx <= 4; $dx++) {
                $xx = $cx + $dx;
                $yy = $cy + $dy;
                if ($xx < 0 || $xx >= $size || $yy < 0 || $yy >= $size) {
                    continue;
                }
                $dist = max(abs($dx), abs($dy));
                self::setFn($modules, $isFn, $xx, $yy, $dist !== 2 && $dist !== 4);
            }
        }
    }

    /**
     * @param list<list<bool>> $modules
     * @param list<list<bool>> $isFn
     */
    private static function drawAlignment(array &$modules, array &$isFn, int $cx, int $cy): void {
        for ($dy = -2; $dy <= 2; $dy++) {
            for ($dx = -2; $dx <= 2; $dx++) {
                self::setFn($modules, $isFn, $cx + $dx, $cy + $dy, max(abs($dx), abs($dy)) !== 1);
            }
        }
    }

    /**
     * @param list<list<bool>> $modules
     * @param list<list<bool>> $isFn
     */
    private static function drawFormatBits(array &$modules, array &$isFn, int $ecl, int $mask, int $size): void {
        $formatBits = [1, 0, 3, 2]; // L M Q H
        $data = ($formatBits[$ecl] << 3) | $mask;
        $rem = $data;
        for ($i = 0; $i < 10; $i++) {
            $rem = ($rem << 1) ^ ((($rem >> 9) * 0x537));
        }
        $bits = (($data << 10) | $rem) ^ 0x5412;

        for ($i = 0; $i < 6; $i++) {
            self::setFn($modules, $isFn, 8, $i, (($bits >> $i) & 1) === 1);
        }
        self::setFn($modules, $isFn, 8, 7, (($bits >> 6) & 1) === 1);
        self::setFn($modules, $isFn, 8, 8, (($bits >> 7) & 1) === 1);
        self::setFn($modules, $isFn, 7, 8, (($bits >> 8) & 1) === 1);
        for ($i = 9; $i < 15; $i++) {
            self::setFn($modules, $isFn, 14 - $i, 8, (($bits >> $i) & 1) === 1);
        }
        for ($i = 0; $i < 8; $i++) {
            self::setFn($modules, $isFn, $size - 1 - $i, 8, (($bits >> $i) & 1) === 1);
        }
        for ($i = 8; $i < 15; $i++) {
            self::setFn($modules, $isFn, 8, $size - 15 + $i, (($bits >> $i) & 1) === 1);
        }
        self::setFn($modules, $isFn, 8, $size - 8, true);
    }

    /**
     * @param list<list<bool>> $modules
     * @param list<list<bool>> $isFn
     */
    private static function drawVersion(array &$modules, array &$isFn, int $version, int $size): void {
        if ($version < 7) {
            return;
        }
        $rem = $version;
        for ($i = 0; $i < 12; $i++) {
            $rem = ($rem << 1) ^ ((($rem >> 11) * 0x1F25));
        }
        $bits = ($version << 12) | $rem;
        for ($i = 0; $i < 18; $i++) {
            $bit = (($bits >> $i) & 1) === 1;
            $a = $size - 11 + ($i % 3);
            $b = intdiv($i, 3);
            self::setFn($modules, $isFn, $a, $b, $bit);
            self::setFn($modules, $isFn, $b, $a, $bit);
        }
    }

    /**
     * @param list<list<bool>> $modules
     * @param list<list<bool>> $isFn
     */
    private static function setFn(array &$modules, array &$isFn, int $x, int $y, bool $dark): void {
        $modules[$y][$x] = $dark;
        $isFn[$y][$x] = true;
    }

    /**
     * @param list<int> $data
     * @return list<int>
     */
    private static function addEccAndInterleave(int $version, int $ecl, array $data): array {
        $numBlocks = self::ECC_BLOCKS[$ecl][$version];
        $blockEccLen = self::ECC_PER_BLOCK[$ecl][$version];
        $rawCodewords = intdiv(self::numRawDataModules($version), 8);
        $numShortBlocks = $numBlocks - ($rawCodewords % $numBlocks);
        $shortBlockLen = intdiv($rawCodewords, $numBlocks);
        $rsDiv = self::rsDivisor($blockEccLen);

        $blocks = [];
        $k = 0;
        for ($i = 0; $i < $numBlocks; $i++) {
            $take = $shortBlockLen - $blockEccLen + ($i < $numShortBlocks ? 0 : 1);
            $dat = array_slice($data, $k, $take);
            $k += count($dat);
            $ecc = self::rsRemainder($dat, $rsDiv);
            if ($i < $numShortBlocks) {
                $dat[] = 0;
            }
            $blocks[] = array_merge($dat, $ecc);
        }

        $result = [];
        $blockLen = count($blocks[0]);
        for ($i = 0; $i < $blockLen; $i++) {
            foreach ($blocks as $j => $blk) {
                if ($i !== ($shortBlockLen - $blockEccLen) || $j >= $numShortBlocks) {
                    $result[] = $blk[$i];
                }
            }
        }
        return $result;
    }

    /**
     * @param list<list<bool>> $modules
     * @param list<list<bool>> $isFn
     * @param list<int> $data
     */
    private static function drawCodewords(array &$modules, array $isFn, array $data): void {
        $size = count($modules);
        $i = 0;
        $totalBits = count($data) * 8;
        for ($rightCol = $size - 1; $rightCol > 0; $rightCol -= 2) {
            $right = $rightCol;
            if ($right <= 6) {
                $right--;
            }
            for ($vert = 0; $vert < $size; $vert++) {
                for ($j = 0; $j < 2; $j++) {
                    $x = $right - $j;
                    $upward = (($right + 1) & 2) === 0;
                    $y = $upward ? ($size - 1 - $vert) : $vert;
                    if (!$isFn[$y][$x] && $i < $totalBits) {
                        $modules[$y][$x] = ((($data[$i >> 3] >> (7 - ($i & 7))) & 1) === 1);
                        $i++;
                    }
                }
            }
        }
    }

    /**
     * @param list<list<bool>> $modules
     * @param list<list<bool>> $isFn
     */
    private static function applyMask(array &$modules, array $isFn, int $mask): void {
        $size = count($modules);
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if ($isFn[$y][$x]) {
                    continue;
                }
                $invert = false;
                switch ($mask) {
                    case 0: $invert = (($x + $y) % 2) === 0; break;
                    case 1: $invert = ($y % 2) === 0; break;
                    case 2: $invert = ($x % 3) === 0; break;
                    case 3: $invert = (($x + $y) % 3) === 0; break;
                    case 4: $invert = ((intdiv($x, 3) + intdiv($y, 2)) % 2) === 0; break;
                    case 5: $invert = (($x * $y % 2) + ($x * $y % 3)) === 0; break;
                    case 6: $invert = ((($x * $y % 2) + ($x * $y % 3)) % 2) === 0; break;
                    case 7: $invert = (((($x + $y) % 2) + ($x * $y % 3)) % 2) === 0; break;
                }
                if ($invert) {
                    $modules[$y][$x] = !$modules[$y][$x];
                }
            }
        }
    }

    /** @param list<list<bool>> $modules */
    private static function penaltyScore(array $modules, int $size): int {
        $result = 0;
        for ($y = 0; $y < $size; $y++) {
            $run = 1;
            for ($x = 1; $x < $size; $x++) {
                if ($modules[$y][$x] === $modules[$y][$x - 1]) {
                    $run++;
                    if ($run === 5) {
                        $result += 3;
                    } elseif ($run > 5) {
                        $result++;
                    }
                } else {
                    $run = 1;
                }
            }
        }
        for ($x = 0; $x < $size; $x++) {
            $run = 1;
            for ($y = 1; $y < $size; $y++) {
                if ($modules[$y][$x] === $modules[$y - 1][$x]) {
                    $run++;
                    if ($run === 5) {
                        $result += 3;
                    } elseif ($run > 5) {
                        $result++;
                    }
                } else {
                    $run = 1;
                }
            }
        }
        for ($y = 0; $y < $size - 1; $y++) {
            for ($x = 0; $x < $size - 1; $x++) {
                $v = $modules[$y][$x];
                if ($v === $modules[$y][$x + 1] && $v === $modules[$y + 1][$x] && $v === $modules[$y + 1][$x + 1]) {
                    $result += 3;
                }
            }
        }
        $dark = 0;
        foreach ($modules as $row) {
            foreach ($row as $cell) {
                if ($cell) {
                    $dark++;
                }
            }
        }
        $total = $size * $size;
        $k = intdiv(abs($dark * 20 - $total * 10) + $total - 1, $total) - 1;
        if ($k < 0) {
            $k = 0;
        }
        $result += $k * 10;
        return $result;
    }

    /** @return list<int> */
    private static function rsDivisor(int $degree): array {
        $result = array_fill(0, $degree - 1, 0);
        $result[] = 1;
        $root = 1;
        for ($i = 0; $i < $degree; $i++) {
            for ($j = 0; $j < $degree; $j++) {
                $result[$j] = self::rsMul($result[$j], $root);
                if ($j + 1 < $degree) {
                    $result[$j] ^= $result[$j + 1];
                }
            }
            $root = self::rsMul($root, 0x02);
        }
        return $result;
    }

    /**
     * @param list<int> $data
     * @param list<int> $divisor
     * @return list<int>
     */
    private static function rsRemainder(array $data, array $divisor): array {
        $result = array_fill(0, count($divisor), 0);
        foreach ($data as $b) {
            $factor = $b ^ array_shift($result);
            $result[] = 0;
            foreach ($divisor as $i => $coef) {
                $result[$i] ^= self::rsMul($coef, $factor);
            }
        }
        return $result;
    }

    private static function rsMul(int $x, int $y): int {
        $z = 0;
        for ($i = 7; $i >= 0; $i--) {
            $z = ($z << 1) ^ (($z >> 7) * 0x11D);
            $z ^= (($y >> $i) & 1) * $x;
        }
        return $z & 0xFF;
    }

    /** ECC codewords per block. Index 0 unused. Medium = row 1. */
    private const ECC_PER_BLOCK = [
        [-1, 7, 10, 15, 20, 26, 18, 20, 24, 30, 18, 20, 24, 26, 30],
        [-1, 10, 16, 26, 18, 24, 16, 18, 22, 22, 26, 30, 22, 22, 24],
        [-1, 13, 22, 18, 26, 18, 24, 18, 22, 20, 24, 28, 26, 24, 20],
        [-1, 17, 28, 22, 16, 22, 28, 26, 26, 24, 28, 24, 28, 22, 24],
    ];

    private const ECC_BLOCKS = [
        [-1, 1, 1, 1, 1, 1, 2, 2, 2, 2, 4, 4, 4, 4, 4],
        [-1, 1, 1, 1, 2, 2, 4, 4, 4, 5, 5, 5, 8, 9, 9],
        [-1, 1, 1, 2, 2, 4, 4, 6, 6, 8, 8, 8, 10, 12, 16],
        [-1, 1, 1, 2, 4, 4, 4, 5, 6, 8, 8, 11, 11, 16, 16],
    ];
}
