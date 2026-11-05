<?php

use App\Models\Payslip;
use App\Services\PayrollCalculator;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Payslip::where('unpaid_leave_days', '>', 0)->get()->each(function (Payslip $payslip) {
            $employee = $payslip->employee()->with('leaveRequests')->first();

            if (! $employee) {
                return;
            }

            $result = app(PayrollCalculator::class)->calculate($employee, $payslip->run->payPeriod());

            $payslip->update([
                'days_paid' => $result['days_paid'],
                'unpaid_leave_days' => $result['unpaid_leave_days'],
                'basic' => $result['basic'],
                'housing' => $result['housing'],
                'transport' => $result['transport'],
                'gross' => $result['gross'],
                'pension' => $result['pension'],
                'tax' => $result['tax'],
                'net' => $result['net'],
            ]);
        });
    }

    public function down(): void
    {
        // Data migration; not reversible.
    }
};
