<?php

namespace Tests\Unit;

use App\Services\TaxTable;
use PHPUnit\Framework\TestCase;

class TaxTableTest extends TestCase
{
    public function test_first_band_is_tax_free(): void
    {
        $this->assertSame(0.0, TaxTable::annualTax(800_000_00));
        $this->assertSame(0.0, TaxTable::annualTax(0));
    }

    public function test_bands_are_applied_in_slices(): void
    {
        // 1.2m at 10%
        $this->assertEqualsWithDelta(120_000_00, TaxTable::annualTax(2_000_000_00), 0.001);
        // + 3m at 15%
        $this->assertEqualsWithDelta(570_000_00, TaxTable::annualTax(5_000_000_00), 0.001);
        // + 7m at 20%, + 1m at 25%
        $this->assertEqualsWithDelta(2_220_000_00, TaxTable::annualTax(13_000_000_00), 0.001);
    }

    public function test_monthly_tax_is_a_twelfth_of_the_year(): void
    {
        $this->assertEqualsWithDelta(10_000_00, TaxTable::monthlyTax(2_000_000_00 / 12), 0.001);
    }
}
