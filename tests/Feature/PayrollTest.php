<?php

namespace Tests\Feature;

use App\Models\PayrollRun;
use App\Models\User;
use App\Services\PayPeriod;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_full_month_payslip(): void
    {
        $grade = $this->grade(['basic' => 200_000_00, 'housing' => 70_000_00, 'transport' => 30_000_00]);
        $employee = $this->employee(['grade_id' => $grade->id, 'loan_repayment' => 25_000_00, 'loan_balance' => 100_000_00]);

        $run = app(PayrollService::class)->createRun(PayPeriod::fromKey('2026-03'));
        $slip = $run->payslips()->first();

        $this->assertSame($employee->id, $slip->employee_id);
        $this->assertSame(31, $slip->days_paid);
        $this->assertSame(300_000_00, $slip->gross);
        $this->assertSame(24_000_00, $slip->pension);
        // (300,000 - 24,000) x 12 = 3,312,000 a year: 120,000 + 15% of 1,312,000, over 12 months.
        $this->assertSame(26_400_00, $slip->tax);
        $this->assertSame(25_000_00, $slip->loan);
        $this->assertSame(224_600_00, $slip->net);
    }

    public function test_staff_who_left_before_the_month_are_not_paid(): void
    {
        $this->employee(['name' => 'Still here']);
        $this->employee(['name' => 'Gone', 'end_date' => '2026-01-31']);

        $run = app(PayrollService::class)->createRun(PayPeriod::fromKey('2026-03'));

        $this->assertSame(['Still here'], $run->payslips()->with('employee')->get()->pluck('employee.name')->all());
    }

    public function test_loan_instalment_is_capped_at_the_balance_and_reduced_on_approval(): void
    {
        $employee = $this->employee(['loan_repayment' => 20_000_00, 'loan_balance' => 12_000_00]);
        $service = app(PayrollService::class);

        $run = $service->createRun(PayPeriod::fromKey('2026-03'));
        $this->assertSame(12_000_00, $run->payslips()->first()->loan);

        $service->approve($run);

        $this->assertSame(PayrollRun::APPROVED, $run->fresh()->status);
        $this->assertSame(0, $employee->fresh()->loan_balance);
    }

    public function test_payroll_officer_can_run_payroll_from_the_screen(): void
    {
        $this->employee();
        $user = $this->user();

        $this->actingAs($user)->post('/payroll', ['period' => '2026-03'])->assertRedirect();
        $this->actingAs($user)->post('/payroll', ['period' => '2026-03'])->assertSessionHasErrors('period');

        $this->assertSame(1, PayrollRun::where('period', '2026-03')->count());
    }

    public function test_only_the_owner_can_approve(): void
    {
        $this->employee();
        $run = app(PayrollService::class)->createRun(PayPeriod::fromKey('2026-03'));

        $this->actingAs($this->user())->post("/payroll/{$run->id}/approve")->assertForbidden();
        $this->actingAs($this->user(User::OWNER))->post("/payroll/{$run->id}/approve")->assertRedirect();

        $this->assertSame(PayrollRun::APPROVED, $run->fresh()->status);
    }

    public function test_the_net_pay_total_on_the_run_page_is_the_sum_of_the_net_pay_column(): void
    {
        $grade = $this->grade(['basic' => 200_000_00, 'housing' => 70_000_00, 'transport' => 30_000_00]);
        $this->employee(['grade_id' => $grade->id]);
        $this->employee(['grade_id' => $grade->id, 'start_date' => '2026-03-10']);
        $this->employee(['grade_id' => $grade->id, 'loan_repayment' => 25_000_00, 'loan_balance' => 100_000_00]);

        $run = app(PayrollService::class)->createRun(PayPeriod::fromKey('2026-03'));

        $html = $this->actingAs($this->user())->get("/payroll/{$run->id}")->assertOk()->getContent();

        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);

        $headers = $xpath->query('//table/thead//th');
        $netColumn = null;
        foreach ($headers as $index => $th) {
            if (trim($th->textContent) === 'Net pay') {
                $netColumn = $index + 1;
            }
        }
        $this->assertNotNull($netColumn, 'The payroll table has no Net pay column');

        $netTotalCell = $xpath->query("//table/tfoot//td[position()={$netColumn}]");
        $this->assertSame(1, $netTotalCell->length, 'The payroll table footer has no Net pay total cell');

        $shownTotal = (int) preg_replace('/[^0-9]/', '', $netTotalCell->item(0)->textContent);

        $expectedNet = $run->payslips()->sum('net');
        $expectedGross = $run->payslips()->sum('gross');
        $this->assertNotSame($expectedGross, $expectedNet);

        $this->assertSame((int) round($expectedNet / 100), $shownTotal);
    }

    public function test_summary_export_lists_staff_and_a_total(): void
    {
        $this->employee(['name' => 'Ada Obi']);
        $this->employee(['name' => 'Bola Ade']);
        $run = app(PayrollService::class)->createRun(PayPeriod::fromKey('2026-03'));

        $csv = $this->actingAs($this->user())->get("/payroll/{$run->id}/export")->assertOk()->getContent();

        $lines = array_values(array_filter(explode("\n", $csv)));
        $this->assertStringContainsString('Staff no', $lines[0]);
        $this->assertStringContainsString('Ada Obi', $lines[1]);
        $this->assertStringContainsString('Bola Ade', $lines[2]);
        $this->assertStringContainsString('TOTAL', $lines[3]);
    }
}
