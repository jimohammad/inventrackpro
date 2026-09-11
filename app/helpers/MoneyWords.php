<?php

/**
 * Amount in words for KWD receipts (3 decimal fils).
 * English + Arabic — GCC commercial / cheque style.
 */
class MoneyWords
{
    /**
     * @return array{en:string,ar:string}
     */
    public static function kwd(float $amount): array
    {
        $scaled = (int) round(round($amount, 3) * 1000);
        $negative = $scaled < 0;
        $scaled = abs($scaled);
        $dinars = intdiv($scaled, 1000);
        $fils = $scaled % 1000;

        $en = self::englishKwd($dinars, $fils);
        $ar = self::arabicKwd($dinars, $fils);
        if ($negative) {
            $en = 'Minus ' . $en;
            $ar = 'سالب ' . $ar;
        }

        return ['en' => $en, 'ar' => $ar];
    }

    private static function englishKwd(int $dinars, int $fils): string
    {
        if ($dinars === 0 && $fils === 0) {
            return 'Zero Kuwaiti Dinars Only';
        }
        $parts = [];
        if ($dinars > 0) {
            $noun = $dinars === 1 ? 'Kuwaiti Dinar' : 'Kuwaiti Dinars';
            $parts[] = self::englishInt($dinars) . ' ' . $noun;
        }
        if ($fils > 0) {
            $noun = $fils === 1 ? 'Fils' : 'Fils';
            $parts[] = self::englishInt($fils) . ' ' . $noun;
        }

        return implode(' and ', $parts) . ' Only';
    }

    private static function arabicKwd(int $dinars, int $fils): string
    {
        if ($dinars === 0 && $fils === 0) {
            return 'صفر دينار كويتي فقط لا غير';
        }
        $bits = [];
        if ($dinars > 0) {
            $bits[] = self::arabicInt($dinars) . ' ' . self::arabicDinarNoun($dinars);
        }
        if ($fils > 0) {
            $bits[] = self::arabicInt($fils) . ' ' . self::arabicFilsNoun($fils);
        }

        return 'فقط ' . implode(' و', $bits) . ' لا غير';
    }

    private static function arabicDinarNoun(int $n): string
    {
        $mod100 = $n % 100;
        if ($n === 1) {
            return 'دينار كويتي';
        }
        if ($n === 2) {
            return 'ديناران كويتيان';
        }
        if ($mod100 >= 3 && $mod100 <= 10) {
            return 'دنانير كويتية';
        }

        return 'دينار كويتي';
    }

    private static function arabicFilsNoun(int $n): string
    {
        $mod100 = $n % 100;
        if ($n === 1) {
            return 'فلس';
        }
        if ($n === 2) {
            return 'فلسان';
        }
        if ($mod100 >= 3 && $mod100 <= 10) {
            return 'فلوس';
        }

        return 'فلساً';
    }

    private static function englishInt(int $n): string
    {
        if ($n === 0) {
            return 'Zero';
        }
        $ones = [
            '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
            'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
            'Seventeen', 'Eighteen', 'Nineteen',
        ];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $under100 = static function (int $x) use ($ones, $tens): string {
            if ($x < 20) {
                return $ones[$x];
            }
            $t = intdiv($x, 10);
            $o = $x % 10;

            return $tens[$t] . ($o ? '-' . $ones[$o] : '');
        };
        $under1000 = static function (int $x) use ($under100, $ones): string {
            if ($x < 100) {
                return $under100($x);
            }
            $h = intdiv($x, 100);
            $r = $x % 100;
            $out = $ones[$h] . ' Hundred';
            if ($r) {
                $out .= ' ' . $under100($r);
            }

            return $out;
        };

        $scales = [
            [1000000, 'Million'],
            [1000, 'Thousand'],
        ];
        $parts = [];
        $rest = $n;
        foreach ($scales as [$size, $name]) {
            if ($rest < $size) {
                continue;
            }
            $chunk = intdiv($rest, $size);
            $rest %= $size;
            $parts[] = $under1000($chunk) . ' ' . $name;
        }
        if ($rest > 0) {
            $parts[] = $under1000($rest);
        }

        return implode(' ', $parts);
    }

    private static function arabicInt(int $n): string
    {
        if ($n === 0) {
            return 'صفر';
        }

        $parts = [];
        $millions = intdiv($n, 1000000);
        $thousands = intdiv($n % 1000000, 1000);
        $rest = $n % 1000;

        if ($millions > 0) {
            $parts[] = self::arabicScale($millions, 'مليون', 'مليونان', 'ملايين', 'مليوناً');
        }
        if ($thousands > 0) {
            $parts[] = self::arabicScale($thousands, 'ألف', 'ألفان', 'آلاف', 'ألفاً');
        }
        if ($rest > 0) {
            $parts[] = self::arabicUnder1000($rest);
        }

        return implode(' و', $parts);
    }

    private static function arabicScale(int $n, string $one, string $two, string $few, string $acc): string
    {
        if ($n === 1) {
            return $one;
        }
        if ($n === 2) {
            return $two;
        }
        $mod100 = $n % 100;
        if ($mod100 >= 3 && $mod100 <= 10) {
            return self::arabicUnder1000($n) . ' ' . $few;
        }

        return self::arabicUnder1000($n) . ' ' . $acc;
    }

    private static function arabicUnder1000(int $n): string
    {
        if ($n <= 0) {
            return '';
        }
        $ones = ['', 'واحد', 'اثنان', 'ثلاثة', 'أربعة', 'خمسة', 'ستة', 'سبعة', 'ثمانية', 'تسعة'];
        $teens = [
            'عشرة', 'أحد عشر', 'اثنا عشر', 'ثلاثة عشر', 'أربعة عشر',
            'خمسة عشر', 'ستة عشر', 'سبعة عشر', 'ثمانية عشر', 'تسعة عشر',
        ];
        $tens = ['', '', 'عشرون', 'ثلاثون', 'أربعون', 'خمسون', 'ستون', 'سبعون', 'ثمانون', 'تسعون'];
        $hundreds = [
            '', 'مائة', 'مائتان', 'ثلاثمائة', 'أربعمائة',
            'خمسمائة', 'ستمائة', 'سبعمائة', 'ثمانمائة', 'تسعمائة',
        ];

        $h = intdiv($n, 100);
        $r = $n % 100;
        $bits = [];
        if ($h > 0) {
            $bits[] = $hundreds[$h];
        }
        if ($r === 0) {
            return implode(' و', $bits);
        }
        if ($r < 10) {
            $bits[] = $ones[$r];
        } elseif ($r < 20) {
            $bits[] = $teens[$r - 10];
        } else {
            $o = $r % 10;
            $t = intdiv($r, 10);
            if ($o === 0) {
                $bits[] = $tens[$t];
            } else {
                $bits[] = $ones[$o] . ' و' . $tens[$t];
            }
        }

        return implode(' و', $bits);
    }
}
