<?php

namespace App\Models;

use App\Services\PayPeriod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['period', 'status', 'created_by', 'approved_at'])]
class PayrollRun extends Model
{
    public const DRAFT = 'draft';

    public const APPROVED = 'approved';

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payPeriod(): PayPeriod
    {
        return PayPeriod::fromKey($this->period);
    }

    public function isDraft(): bool
    {
        return $this->status === self::DRAFT;
    }
}
