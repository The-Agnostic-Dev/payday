<?php

namespace Tests;

use App\Models\Employee;
use App\Models\Grade;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    private int $staffCounter = 0;

    protected function grade(array $attributes = []): Grade
    {
        return Grade::create($attributes + [
            'code' => 'T'.random_int(100, 999),
            'name' => 'Test grade',
            'basic' => 120_000_00,
            'housing' => 40_000_00,
            'transport' => 20_000_00,
        ]);
    }

    protected function employee(array $attributes = []): Employee
    {
        $this->staffCounter++;

        return Employee::create($attributes + [
            'staff_no' => sprintf('PD-%03d', $this->staffCounter),
            'name' => 'Staff '.$this->staffCounter,
            'department' => 'Sales',
            'job_title' => 'Sales Officer',
            'grade_id' => $attributes['grade_id'] ?? $this->grade()->id,
            'start_date' => '2020-01-06',
        ]);
    }

    protected function user(string $role = User::PAYROLL, ?Employee $employee = null): User
    {
        return User::create([
            'name' => ucfirst($role).' user',
            'email' => $role.random_int(1000, 9999).'@example.test',
            'password' => 'secret-password',
            'role' => $role,
            'employee_id' => $employee?->id,
        ]);
    }
}
