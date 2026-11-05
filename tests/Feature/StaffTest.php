<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
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

    public function test_dashboard_active_staff_count_excludes_employees_who_have_left(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::where('email', 'bisi@adunnifoods.ng')->firstOrFail();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Active staff 14')
            ->assertDontSee('Active staff 15');
    }
}
