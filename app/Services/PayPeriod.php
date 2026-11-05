<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * A payroll month, written "YYYY-MM".
 */
final class PayPeriod
{
    public readonly CarbonImmutable $start;

    public readonly CarbonImmutable $end;

    private function __construct(public readonly string $key)
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $key)) {
            throw new InvalidArgumentException("Not a payroll period: {$key}");
        }

        $this->start = CarbonImmutable::createFromFormat('!Y-m', $key, config('app.timezone'));
        $this->end = $this->start->endOfMonth();
    }

    public static function fromKey(string $key): self
    {
        return new self($key);
    }

    public static function current(): self
    {
        return new self(CarbonImmutable::now(config('app.timezone'))->format('Y-m'));
    }

    public function previous(int $months = 1): self
    {
        return new self($this->start->subMonthsNoOverflow($months)->format('Y-m'));
    }

    public function next(): self
    {
        return new self($this->start->addMonthNoOverflow()->format('Y-m'));
    }

    public function label(): string
    {
        return $this->start->format('F Y');
    }

    public function daysInMonth(): int
    {
        return $this->start->daysInMonth;
    }

    /** Whether someone with these dates worked any part of the month. */
    public function covers(CarbonImmutable $startDate, ?CarbonImmutable $endDate): bool
    {
        return $startDate->lte($this->end) && ($endDate === null || $endDate->gte($this->start));
    }

    /**
     * Calendar days in the month a staff member is on the books, counting
     * their first and last day.
     */
    public function daysEmployed(CarbonImmutable $startDate, ?CarbonImmutable $endDate): int
    {
        if (! $this->covers($startDate, $endDate)) {
            return 0;
        }

        $joinedThisMonth = $startDate->gt($this->start);
        $leftThisMonth = $endDate !== null && $endDate->lt($this->end);

        if (! $joinedThisMonth && ! $leftThisMonth) {
            return $this->daysInMonth();
        }

        $from = $joinedThisMonth ? $startDate : $this->start;
        $to = $leftThisMonth ? $endDate : $this->end;

        return (int) $from->diffInDays($to) + 1;
    }

    /** Calendar days of [from, to] that fall inside this month. */
    public function overlapDays(CarbonImmutable $from, CarbonImmutable $to): int
    {
        $from = $from->max($this->start)->startOfDay();
        $to = $to->min($this->end)->startOfDay();

        return $from->gt($to) ? 0 : (int) $from->diffInDays($to) + 1;
    }
}
