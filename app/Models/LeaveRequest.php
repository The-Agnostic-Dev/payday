<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['employee_id', 'type', 'start_date', 'end_date', 'days', 'note'])]
class LeaveRequest extends Model
{
    public const ANNUAL = 'annual';

    public const SICK = 'sick';

    public const UNPAID = 'unpaid';

    public const TYPES = [
        self::ANNUAL => 'Annual leave',
        self::SICK => 'Sick leave',
        self::UNPAID => 'Unpaid leave',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'immutable_date',
            'end_date' => 'immutable_date',
            'days' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }
}
