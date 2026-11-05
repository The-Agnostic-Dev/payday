<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveRequest;

/**
 * Works out one staff member's payslip for one month. Pure arithmetic on
 * kobo; nothing is saved here (see PayrollService).
 */
class PayrollCalculator
{
    public const PENSION_RATE = 0.08;

    /**
     * @return array<string, int> payslip attributes
     */
    public function calculate(Employee $employee, PayPeriod $period): array
    {
        $daysInMonth = $period->daysInMonth();
        $daysEmployed = $period->daysEmployed($employee->start_date, $employee->end_date);
        $unpaidDays = min($daysEmployed, $this->unpaidLeaveDays($employee, $period));
        $daysPaid = $daysEmployed - $unpaidDays;

        $share = $daysPaid / $daysInMonth;

        $gross = (int) round($employee->monthlyGross() * $share);
        $basic = (int) round($employee->basic * $share);
        $housing = (int) round($employee->housing * $share);
        $transport = $gross - $basic - $housing;

        if ($transport < 0) {
            $excess = -$transport;
            if ($housing >= $excess) {
                $housing -= $excess;
            } else {
                $basic -= ($excess - $housing);
                $housing = 0;
            }
            $transport = 0;
        }

        $pension = $gross * self::PENSION_RATE;
        $tax = TaxTable::monthlyTax($gross - $pension);
        $loan = $this->loanDeduction($employee);

        $pension = (int) round($pension);
        $tax = (int) round($tax);

        return [
            'employee_id' => $employee->id,
            'days_in_month' => $daysInMonth,
            'days_paid' => $daysPaid,
            'unpaid_leave_days' => $unpaidDays,
            'basic' => $basic,
            'housing' => $housing,
            'transport' => $transport,
            'gross' => $gross,
            'pension' => $pension,
            'tax' => $tax,
            'loan' => $loan,
            'net' => $gross - $pension - $tax - $loan,
        ];
    }

    /** The monthly instalment, but never more than what is still owed. */
    public function loanDeduction(Employee $employee): int
    {
        return min($employee->loan_repayment, $employee->loan_balance);
    }

    /** Calendar days of unpaid leave that fall inside the month. */
    public function unpaidLeaveDays(Employee $employee, PayPeriod $period): int
    {
        return $employee->leaveRequests
            ->where('type', LeaveRequest::UNPAID)
            ->sum(fn (LeaveRequest $leave) => $period->overlapDays($leave->start_date, $leave->end_date));
    }
}
