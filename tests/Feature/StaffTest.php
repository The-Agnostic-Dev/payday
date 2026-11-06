<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffTest extends TestCase
{
    use RefreshDatabase;

    public function test_adding_staff_takes_pay_from_the_grade(): void
    {
        $grade = $this->grade(['basic' => 70_000_00, 'housing' => 20_000_00, 'transport' => 10_000_00]);

        $this->actingAs($this->user())->post('/staff', [
            'staff_no' => 'PD-050',
            'name' => 'Kemi Lawal',
            'department' => 'Admin',
            'job_title' => 'Receptionist',
            'grade_id' => $grade->id,
            'start_date' => '2026-03-02',
            'account_number' => '0123456789',
            'loan_repayment' => '5000',
            'loan_balance' => '',
        ])->assertRedirect();

        $employee = Employee::where('staff_no', 'PD-050')->firstOrFail();
        $this->assertSame(100_000_00, $employee->monthlyGross());
        $this->assertSame(5_000_00, $employee->loan_repayment);
        $this->assertSame(0, $employee->loan_balance);
    }

    public function test_editing_phone_keeps_loan_amounts(): void
    {
        $this->seed();

        $employee = Employee::where('staff_no', 'PD-005')->firstOrFail();
        $this->assertSame(2_500_000, $employee->loan_repayment);
        $this->assertSame(6_000_000, $employee->loan_balance);

        $this->actingAs($this->user())->put("/staff/{$employee->id}", [
            'staff_no' => $employee->staff_no,
            'name' => $employee->name,
            'email' => $employee->email,
            'phone' => '08099998888',
            'department' => $employee->department,
            'job_title' => $employee->job_title,
            'grade_id' => $employee->grade_id,
            'start_date' => '2020-11-02',
            'bank_name' => $employee->bank_name,
            'account_number' => $employee->account_number,
            'loan_repayment' => '25,000.00',
            'loan_balance' => '60,000.00',
        ])->assertRedirect();

        $employee->refresh();
        $this->assertSame(2_500_000, $employee->loan_repayment);
        $this->assertSame(6_000_000, $employee->loan_balance);

        $this->actingAs($this->user())->get("/staff/{$employee->id}")
            ->assertOk()
            ->assertSee('25,000.00')
            ->assertSee('60,000.00');

        $this->actingAs($this->user())->put("/staff/{$employee->id}", [
            'staff_no' => $employee->staff_no,
            'name' => $employee->name,
            'email' => $employee->email,
            'phone' => '08099998888',
            'department' => $employee->department,
            'job_title' => $employee->job_title,
            'grade_id' => $employee->grade_id,
            'start_date' => '2020-11-02',
            'bank_name' => $employee->bank_name,
            'account_number' => $employee->account_number,
            'loan_repayment' => '25,000',
            'loan_balance' => '60,000.00',
        ])->assertRedirect();

        $this->assertSame(2_500_000, $employee->fresh()->loan_repayment);
    }

    public function test_changing_grade_changes_pay(): void
    {
        $employee = $this->employee();
        $better = $this->grade(['basic' => 200_000_00, 'housing' => 70_000_00, 'transport' => 30_000_00]);

        $employee->update(['grade_id' => $better->id]);

        $this->assertSame(300_000_00, $employee->fresh()->monthlyGross());
    }

    public function test_account_numbers_must_be_ten_digits(): void
    {
        $employee = $this->employee();

        $this->actingAs($this->user())->put("/staff/{$employee->id}", [
            'staff_no' => $employee->staff_no,
            'name' => $employee->name,
            'department' => 'Sales',
            'job_title' => 'Sales Officer',
            'grade_id' => $employee->grade_id,
            'start_date' => '2020-01-06',
            'account_number' => '12345',
        ])->assertSessionHasErrors('account_number');
    }

    public function test_leave_is_counted_in_working_days(): void
    {
        $employee = $this->employee();

        // Friday 6 March to Monday 9 March 2026
        $this->actingAs($this->user())->post('/leave', [
            'employee_id' => $employee->id,
            'type' => LeaveRequest::ANNUAL,
            'start_date' => '2026-03-06',
            'end_date' => '2026-03-09',
        ])->assertRedirect('/leave');

        $this->assertSame(2, LeaveRequest::firstOrFail()->days);
    }

    public function test_dashboard_active_staff_excludes_employees_who_have_left(): void
    {
        $grade = $this->grade(['basic' => 70_000_00, 'housing' => 20_000_00, 'transport' => 10_000_00]);

        Employee::create([
            'staff_no' => 'PD-101',
            'name' => 'Active One',
            'email' => 'active1@example.com',
            'phone' => '08011111111',
            'department' => 'Admin',
            'job_title' => 'Officer',
            'grade_id' => $grade->id,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => null,
            'bank_name' => 'GTBank',
            'account_number' => '0123456781',
            'loan_repayment' => 0,
            'loan_balance' => 0,
        ]);

        Employee::create([
            'staff_no' => 'PD-102',
            'name' => 'Active Two',
            'email' => 'active2@example.com',
            'phone' => '08022222222',
            'department' => 'Admin',
            'job_title' => 'Officer',
            'grade_id' => $grade->id,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => null,
            'bank_name' => 'GTBank',
            'account_number' => '0123456782',
            'loan_repayment' => 0,
            'loan_balance' => 0,
        ]);

        Employee::create([
            'staff_no' => 'PD-103',
            'name' => 'Future End',
            'email' => 'future@example.com',
            'phone' => '08033333333',
            'department' => 'Admin',
            'job_title' => 'Officer',
            'grade_id' => $grade->id,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'bank_name' => 'GTBank',
            'account_number' => '0123456783',
            'loan_repayment' => 0,
            'loan_balance' => 0,
        ]);

        Employee::create([
            'staff_no' => 'PD-104',
            'name' => 'Left Employee',
            'email' => 'left@example.com',
            'phone' => '08044444444',
            'department' => 'Admin',
            'job_title' => 'Officer',
            'grade_id' => $grade->id,
            'start_date' => now()->subYears(2)->toDateString(),
            'end_date' => now()->subMonth()->toDateString(),
            'bank_name' => 'GTBank',
            'account_number' => '0123456784',
            'loan_repayment' => 0,
            'loan_balance' => 0,
        ]);

        $this->actingAs($this->user())->get('/dashboard')
            ->assertOk()
            ->assertSee('Active staff')
            ->assertSee('<strong class="value">3</strong>', false);
    }
}
