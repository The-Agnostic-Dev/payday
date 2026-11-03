<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id', 'days_in_month', 'days_paid', 'unpaid_leave_days',
    'basic', 'housing', 'transport', 'gross', 'pension', 'tax', 'loan', 'net',
])]
class Payslip extends Model
{
    protected function casts(): array
    {
        return array_fill_keys(
            ['days_in_month', 'days_paid', 'unpaid_leave_days', 'basic', 'housing', 'transport', 'gross', 'pension', 'tax', 'loan', 'net'],
            'integer',
        );
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function totalDeductions(): int
    {
        return $this->pension + $this->tax + $this->loan;
    }
}
