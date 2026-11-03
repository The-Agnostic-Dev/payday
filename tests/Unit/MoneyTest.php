<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_it_formats_kobo_as_naira(): void
    {
        $this->assertSame('₦12,500.00', Money::format(1_250_000));
        $this->assertSame('₦0.50', Money::format(50));
        $this->assertSame('-₦1,000.00', Money::format(-100_000));
    }

    public function test_it_reads_plain_amounts(): void
    {
        $this->assertSame(2_500_000, Money::parse('25000'));
        $this->assertSame(150_050, Money::parse('₦ 1500.50'));
        $this->assertSame(0, Money::parse(''));
        $this->assertSame(0, Money::parse(null));
    }

    public function test_input_and_plain_values(): void
    {
        $this->assertSame('25,000.00', Money::forInput(2_500_000));
        $this->assertSame('25000.00', Money::plain(2_500_000));
        $this->assertSame(1_234_567, Money::fromNaira('12345.67'));
    }
}
