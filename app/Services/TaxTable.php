<?php

namespace App\Services;

/**
 * Payday's simplified PAYE table. See "Pay rules" in the README: this is
 * the table Adunni Foods agreed with its accountant, not the statutory one.
 */
final class TaxTable
{
    /**
     * Annual bands in kobo: [width of band, rate]. The last band is open-ended.
     *
     * @var list<array{0: int|null, 1: float}>
     */
    public const BANDS = [
        [800_000_00, 0.00],
        [1_200_000_00, 0.10],
        [3_000_000_00, 0.15],
        [7_000_000_00, 0.20],
        [null, 0.25],
    ];

    /** Tax on a year's taxable income, both in kobo (unrounded). */
    public static function annualTax(int|float $taxable): float
    {
        $tax = 0.0;
        $remaining = max(0, $taxable);

        foreach (self::BANDS as [$width, $rate]) {
            $slice = $width === null ? $remaining : min($remaining, $width);
            $tax += $slice * $rate;
            $remaining -= $slice;

            if ($remaining <= 0) {
                break;
            }
        }

        return $tax;
    }

    /** One month's tax on one month's taxable pay, in kobo (unrounded). */
    public static function monthlyTax(int|float $monthlyTaxable): float
    {
        return self::annualTax($monthlyTaxable * 12) / 12;
    }
}
