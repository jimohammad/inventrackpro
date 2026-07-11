<?php

/**
 * Party form phone field: split stored value into country code + local number.
 * Uses longest-prefix match so +96512345678 is not parsed as +9651 + 2345678.
 */
class PhoneInput
{
    /** @var list<string> */
    public const PARTY_COUNTRY_CODES = [
        '+880', '+977', '+971', '+966', '+974', '+968', '+973', '+965',
        '+91', '+852', '+86', '+92', '+63', '+94',
    ];

    /**
     * @return array{cc: string, num: string}
     */
    public static function splitStoredPhone(?string $phone, string $defaultCc = '+965'): array
    {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return ['cc' => $defaultCc, 'num' => ''];
        }

        $normalized = preg_replace('/[\s\-]/', '', $phone) ?? $phone;

        foreach (self::PARTY_COUNTRY_CODES as $cc) {
            if (str_starts_with($normalized, $cc)) {
                return ['cc' => $cc, 'num' => substr($normalized, strlen($cc))];
            }
        }

        if (str_starts_with($normalized, '+')) {
            return ['cc' => $defaultCc, 'num' => ltrim($normalized, '+')];
        }

        return ['cc' => $defaultCc, 'num' => $normalized];
    }
}
