<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable(['name', 'email', 'password', 'role', 'employee_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const PAYROLL = 'payroll';

    public const OWNER = 'owner';

    public const STAFF = 'staff';

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isStaff(): bool
    {
        return $this->role === self::STAFF;
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::PAYROLL => 'HR & payroll',
            self::OWNER => 'Owner',
            default => 'Staff',
        };
    }
}
