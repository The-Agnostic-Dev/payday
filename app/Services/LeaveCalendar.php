<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveRequest;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

class LeaveCalendar
{
    public const ANNUAL_ENTITLEMENT = 20;

    /** Monday to Friday days from start to end, both included. */
    public function workingDays(CarbonImmutable $start, CarbonImmutable $end): int
    {
        if ($end->lt($start)) {
            return 0;
        }

        return collect(CarbonPeriod::create($start, $end))
            ->reject(fn ($day) => $day->isWeekend())
            ->count();
    }

    /** Annual leave days still available this calendar year. */
    public function annualBalance(Employee $employee, ?int $year = null): int
    {
        $year ??= CarbonImmutable::now()->year;

        $taken = $employee->leaveRequests
            ->where('type', LeaveRequest::ANNUAL)
            ->filter(fn (LeaveRequest $leave) => $leave->start_date->year === $year)
            ->sum('days');

        return self::ANNUAL_ENTITLEMENT - $taken;
    }
}
