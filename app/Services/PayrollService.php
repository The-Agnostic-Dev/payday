<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payslip;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function __construct(private readonly PayrollCalculator $calculator) {}

    public function hasRun(PayPeriod $period): bool
    {
        return PayrollRun::where('period', $period->key)->exists();
    }

    /** Staff who worked at least one day of the month. */
    public function staffFor(PayPeriod $period)
    {
        return Employee::with('leaveRequests')
            ->whereDate('start_date', '<=', $period->end)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $period->start))
            ->orderBy('name')
            ->get();
    }

    /** Make a draft run with a payslip for everyone on the books that month. */
    public function createRun(PayPeriod $period, ?User $by = null): PayrollRun
    {
        $slips = $this->staffFor($period)
            ->map(fn (Employee $employee) => $this->calculator->calculate($employee, $period))
            ->filter(fn (array $slip) => $slip['days_paid'] > 0 || $slip['unpaid_leave_days'] > 0)
            ->values();

        return DB::transaction(function () use ($period, $by, $slips) {
            $run = PayrollRun::create([
                'period' => $period->key,
                'status' => PayrollRun::DRAFT,
                'created_by' => $by?->id,
            ]);

            $run->payslips()->createMany($slips->all());

            return $run;
        });
    }

    /** Approve a draft run and take this month's loan instalments off the balances. */
    public function approve(PayrollRun $run): void
    {
        DB::transaction(function () use ($run) {
            $run->load('payslips.employee');

            foreach ($run->payslips as $payslip) {
                if ($payslip->loan > 0) {
                    $employee = $payslip->employee;
                    $employee->loan_balance = max(0, $employee->loan_balance - $payslip->loan);
                    $employee->save();
                }
            }

            $run->update(['status' => PayrollRun::APPROVED, 'approved_at' => now()]);
        });
    }

    public function discard(PayrollRun $run): void
    {
        $run->delete();
    }

    public function recomputeUnpaidLeave(?PayPeriod $period = null): void
    {
        $query = Payslip::where('unpaid_leave_days', '>', 0)
            ->with(['run', 'employee.leaveRequests']);

        if ($period) {
            $query->whereHas('run', fn ($q) => $q->where('period', $period->key));
        }

        $query->get()->each(function (Payslip $payslip) {
            if (! $payslip->employee || $payslip->days_paid <= 0) {
                return;
            }

            $period = $payslip->run->payPeriod();
            $hasOverlappingUnpaidLeave = $payslip->employee->leaveRequests
                ->where('type', LeaveRequest::UNPAID)
                ->contains(fn ($leave) => $leave->start_date <= $period->end && $leave->end_date >= $period->start);

            if (! $hasOverlappingUnpaidLeave) {
                return;
            }

            $storedUnpaidLeave = $payslip->unpaid_leave_days;
            $correctedUnpaidLeave = $this->calculator->unpaidLeaveDays($payslip->employee, $period);

            $correctedUnpaidLeave = min($correctedUnpaidLeave, $storedUnpaidLeave);

            $storedDaysPaid = $payslip->days_paid;
            $newDaysPaid = ($storedDaysPaid + $storedUnpaidLeave) - $correctedUnpaidLeave;
            $ratio = $newDaysPaid / $storedDaysPaid;

            $basic = (int) round($payslip->basic * $ratio);
            $housing = (int) round($payslip->housing * $ratio);
            $transport = (int) round($payslip->transport * $ratio);
            $gross = $basic + $housing + $transport;
            $pension = (int) round($gross * PayrollCalculator::PENSION_RATE);
            $tax = (int) round(TaxTable::monthlyTax($gross - $pension));
            $net = $gross - $pension - $tax - $payslip->loan;

            $payslip->update([
                'days_paid' => $newDaysPaid,
                'unpaid_leave_days' => $correctedUnpaidLeave,
                'basic' => $basic,
                'housing' => $housing,
                'transport' => $transport,
                'gross' => $gross,
                'pension' => $pension,
                'tax' => $tax,
                'net' => $net,
            ]);
        });
    }
}
