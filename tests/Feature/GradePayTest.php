<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Grade;
use App\Models\User;
use App\Services\PayPeriod;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class GradePayTest extends TestCase
{
    use RefreshDatabase;

    public function test_grade_pay_change_shows_on_employee_page_and_next_payroll_run(): void
    {
        $this->seed();

        $grade = Grade::where('code', 'G2')->firstOrFail();
        $employee = Employee::where('staff_no', 'PD-004')->firstOrFail();
        $this->assertSame($grade->id, $employee->grade_id);

        $user = User::where('role', User::PAYROLL)->firstOrFail();

        $newBasic = 135_000 * 100;
        $newHousing = 45_000 * 100;
        $newTransport = 22_000 * 100;

        $grade->update([
            'basic' => $newBasic,
            'housing' => $newHousing,
            'transport' => $newTransport,
        ]);

        $employeeUrl = Route::has('employees.show')
            ? route('employees.show', $employee)
            : '/employees/'.$employee->id;

        $response = $this->actingAs($user)->get($employeeUrl);
        $response->assertOk();

        $html = $response->getContent();
        $this->assertTrue(
            str_contains($html, '135,000') || str_contains($html, '135000'),
            'Employee detail page does not show the updated grade basic pay.'
        );

        $payroll = app(PayrollService::class);
        $run = $payroll->createRun(PayPeriod::current()->next(), $user);

        $slip = $run->payslips()->where('employee_id', $employee->id)->firstOrFail();

        $this->assertEquals($newBasic, $slip->basic);
        $this->assertEquals($newHousing, $slip->housing);
        $this->assertEquals($newTransport, $slip->transport);

        $summaryUrl = null;
        foreach (['payroll.show', 'payroll.runs.show'] as $name) {
            if (Route::has($name)) {
                $summaryUrl = route($name, $run);
                break;
            }
        }
        $summaryUrl ??= '/payroll/'.$run->id;

        $summary = $this->actingAs($user)->get($summaryUrl);
        $summary->assertOk();

        $summaryHtml = $summary->getContent();
        $this->assertTrue(
            str_contains($summaryHtml, '135,000') || str_contains($summaryHtml, '135000'),
            'Payroll summary does not show the updated grade basic pay.'
        );
    }
}
