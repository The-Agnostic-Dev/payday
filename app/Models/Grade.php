<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'basic', 'housing', 'transport'])]
class Grade extends Model
{
    protected function casts(): array
    {
        return [
            'basic' => 'integer',
            'housing' => 'integer',
            'transport' => 'integer',
        ];
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function monthlyGross(): int
    {
        return $this->basic + $this->housing + $this->transport;
    }
}
