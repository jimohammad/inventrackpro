<?php

/**
 * Android-style 3×3 screen lock pattern: stored as comma-separated dot indices 0–8.
 *
 * Grid layout:
 *   0 1 2
 *   3 4 5
 *   6 7 8
 */
class ServiceLockPattern {

    /** @return string|null Normalized pattern or null if empty/invalid */
    public static function parse(?string $raw): ?string {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        $parts = explode(',', $raw);
        if (count($parts) < 2) {
            return null;
        }

        $seen = [];
        $normalized = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if (!preg_match('/^[0-8]$/', $part)) {
                return null;
            }
            if (isset($seen[$part])) {
                return null;
            }
            $seen[$part] = true;
            $normalized[] = $part;
        }

        return implode(',', $normalized);
    }

    /** @return int[] */
    public static function dots(?string $pattern): array {
        $pattern = self::parse($pattern);
        if ($pattern === null) {
            return [];
        }

        return array_map('intval', explode(',', $pattern));
    }

    public static function hasPattern(?string $pattern): bool {
        return count(self::dots($pattern)) >= 2;
    }

    /** @return string|null Normalized numeric screen PIN or null if empty/invalid */
    public static function parseScreenPin(?string $raw): ?string {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        if (!preg_match('/^\d{4,16}$/', $raw)) {
            return null;
        }
        return $raw;
    }

    /**
     * SVG 3×3 grid for print/display. Always renders 9 dots; draws lines when a saved pattern exists.
     */
    public static function toGridSvg(?string $pattern, int $size = 96): string {
        $dots = self::dots($pattern);
        $hasPath = count($dots) >= 2;

        $pad = (int) round($size * 0.18);
        $span = $size - ($pad * 2);
        $step = $span / 2;
        $dotR = max(3, (int) round($size * 0.042));
        $lineW = max(1.8, $size * 0.03);

        $coord = static function (int $index) use ($pad, $step): array {
            $row = intdiv($index, 3);
            $col = $index % 3;
            return [$pad + ($col * $step), $pad + ($row * $step)];
        };

        $lines = '';
        if ($hasPath) {
            for ($i = 0, $n = count($dots); $i < $n - 1; $i++) {
                [$x1, $y1] = $coord($dots[$i]);
                [$x2, $y2] = $coord($dots[$i + 1]);
                $lines .= sprintf(
                    '<line x1="%.2f" y1="%.2f" x2="%.2f" y2="%.2f" stroke="currentColor" stroke-width="%.2f" stroke-linecap="round"/>',
                    $x1,
                    $y1,
                    $x2,
                    $y2,
                    $lineW
                );
            }
        }

        $circles = '';
        for ($i = 0; $i < 9; $i++) {
            [$cx, $cy] = $coord($i);
            $inPath = $hasPath && in_array($i, $dots, true);
            if ($inPath) {
                $circles .= sprintf(
                    '<circle cx="%.2f" cy="%.2f" r="%.2f" fill="currentColor"/>',
                    $cx,
                    $cy,
                    $dotR
                );
            } else {
                $strokeW = max(1.2, $dotR * 0.22);
                $circles .= sprintf(
                    '<circle cx="%.2f" cy="%.2f" r="%.2f" fill="none" stroke="currentColor" stroke-width="%.2f"/>',
                    $cx,
                    $cy,
                    $dotR,
                    $strokeW
                );
            }
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="%d" height="%d" aria-hidden="true" role="img">%s%s</svg>',
            $size,
            $size,
            $size,
            $size,
            $lines,
            $circles
        );
    }

    /** @deprecated Use toGridSvg() — kept for callers expecting path-only render */
    public static function toSvg(?string $pattern, int $size = 88): string {
        if (!self::hasPattern($pattern)) {
            return '';
        }
        return self::toGridSvg($pattern, $size);
    }
}
