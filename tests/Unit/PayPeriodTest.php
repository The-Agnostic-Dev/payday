<?php

namespace Tests\Unit;

use App\Services\PayPeriod;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Tests\TestCase;

class PayPeriodTest extends TestCase
{
    public function test_it_describes_the_month(): void
    {
        $period = PayPeriod::fromKey('2026-02');

        $this->assertSame('February 2026', $period->label());
        $this->assertSame(28, $period->daysInMonth());
        $this->assertSame('2026-01', $period->previous()->key);
        $this->assertSame('2026-03', $period->next()->key);
    }

    public function test_it_rejects_bad_keys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PayPeriod::fromKey('2026-13');
    }

    public function test_full_month_staff_get_every_day(): void
    {
        $period = PayPeriod::fromKey('2026-03');

        $this->assertSame(31, $period->daysEmployed(CarbonImmutable::parse('2020-01-01'), null));
        $this->assertSame(0, $period->daysEmployed(CarbonImmutable::parse('2026-04-02'), null));
        $this->assertSame(0, $period->daysEmployed(CarbonImmutable::parse('2020-01-01'), CarbonImmutable::parse('2026-02-28')));
    }

    public function test_part_month_staff_are_paid_inclusively(): void
    {
        $period = PayPeriod::fromKey('2026-03');

        $this->assertSame(22, $period->daysEmployed(CarbonImmutable::parse('2026-03-10'), null));
        $this->assertSame(20, $period->daysEmployed(CarbonImmutable::parse('2020-01-01'), CarbonImmutable::parse('2026-03-20')));
        $this->assertSame(
            (int) CarbonImmutable::parse('2026-03-05')->diffInDays(CarbonImmutable::parse('2026-03-12')) + 1,
            $period->daysEmployed(CarbonImmutable::parse('2026-03-05'), CarbonImmutable::parse('2026-03-12'))
        );
    }

    public function test_overlap_counts_calendar_days_inside_the_month(): void
    {
        $period = PayPeriod::fromKey('2026-03');

        $this->assertSame(4, $period->overlapDays(CarbonImmutable::parse('2026-03-06'), CarbonImmutable::parse('2026-03-09')));
        $this->assertSame(2, $period->overlapDays(CarbonImmutable::parse('2026-02-25'), CarbonImmutable::parse('2026-03-02')));
        $this->assertSame(0, $period->overlapDays(CarbonImmutable::parse('2026-04-01'), CarbonImmutable::parse('2026-04-03')));
    }
}
