<?php

namespace App\Services;

use App\Models\Employee;
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
}
