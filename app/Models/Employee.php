<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'staff_no', 'name', 'email', 'phone', 'department', 'job_title', 'grade_id',
    'start_date', 'end_date', 'bank_name', 'account_number', 'loan_repayment', 'loan_balance',
])]
class Employee extends Model
{
    protected static function booted(): void
    {
        // Keep the staff member's pay lines in step with their grade.
        static::saving(function (Employee $employee) {
            $grade = $employee->grade()->first();

            if ($grade !== null) {
                $employee->basic = $grade->basic;
                $employee->housing = $grade->housing;
                $employee->transport = $grade->transport;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'immutable_date',
            'end_date' => 'immutable_date',
            'basic' => 'integer',
            'housing' => 'integer',
            'transport' => 'integer',
            'loan_repayment' => 'integer',
            'loan_balance' => 'integer',
        ];
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class)->orderBy('start_date');
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    /** Staff who have not left (a leaving date in the future still counts). */
    public function scopeActive(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q
            ->whereNull('end_date')
            ->orWhereDate('end_date', '>=', CarbonImmutable::today()));
    }

    public function monthlyGross(): int
    {
        return $this->basic + $this->housing + $this->transport;
    }

    public function hasLeft(): bool
    {
        return $this->end_date !== null && $this->end_date->lt(CarbonImmutable::today());
    }

    public function statusLabel(): string
    {
        $today = CarbonImmutable::today();

        return match (true) {
            $this->hasLeft() => 'Left '.$this->end_date->format('j M Y'),
            $this->start_date->gt($today) => 'Starts '.$this->start_date->format('j M Y'),
            $this->end_date !== null => 'Leaving '.$this->end_date->format('j M Y'),
            default => 'Active',
        };
    }
}
