<?php

namespace App\Support;

/**
 * Amounts are kept as whole kobo everywhere in Payday. These helpers turn
 * them into naira for people and back again.
 */
final class Money
{
    public static function format(int $kobo): string
    {
        $sign = $kobo < 0 ? '-' : '';

        return $sign.'₦'.number_format(abs($kobo) / 100, 2);
    }

    /** Value for a text box, e.g. "25,000.00". */
    public static function forInput(int $kobo): string
    {
        return number_format($kobo / 100, 2);
    }

    /** Plain naira value for number inputs and CSV, e.g. "25000.00". */
    public static function plain(int $kobo): string
    {
        return number_format($kobo / 100, 2, '.', '');
    }

    /** Read a naira amount typed by a person (e.g. "₦ 25000") into kobo. */
    public static function parse(?string $value): int
    {
        $clean = str_replace(['₦', 'NGN', ' '], '', trim((string) $value));

        if ($clean === '') {
            return 0;
        }

        return (int) round((float) $clean * 100);
    }

    public static function fromNaira(int|float|string $naira): int
    {
        return (int) round(((float) $naira) * 100);
    }
}
