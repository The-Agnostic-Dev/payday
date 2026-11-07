<?php

namespace Tests\Feature;

use App\Models\PayrollRun;
use App\Models\User;
use App\Services\PayPeriod;
use App\Support\Money;
use App\Services\PayrollService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        $this->employee(['grade_id' => $grade->id, 'loan_repayment' => 25_000_00, 'loan_balance' => 100_000_00]);

        $run = app(PayrollService::class)->createRun(PayPeriod::fromKey('2025-11'));

        $html = $this->actingAs($this->user())->get("/payroll/{$run->id}")->assertOk()->getContent();

        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);

        $parse = fn (string $text): int => (int) round((float) str_replace(['₦', ',', ' '], '', $text) * 100);

        $netCells = $xpath->query('//table[@data-testid="payslips"]/tbody//td[contains(@class, "num") and contains(@class, "net")]');
        $bodySum = 0;
        foreach ($netCells as $cell) {
            $bodySum += $parse($cell->textContent);
        }

        $expectedNet = (int) $run->payslips()->sum('net');
        $this->assertNotSame((int) $run->payslips()->sum('gross'), $expectedNet);
        $this->assertSame($expectedNet, $bodySum);

        $totalCell = $xpath->query('//table[@data-testid="payslips"]/tfoot//th[contains(@class, "num") and contains(@class, "net-total")]');
        $this->assertSame(1, $totalCell->length, 'The payroll table footer has no Net pay total cell');

        $total = $parse($totalCell->item(0)->textContent);
        $this->assertSame($bodySum, $total);
        $this->assertSame($expectedNet, $total);
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

    public function test_running_payroll_twice_for_the_same_month_leaves_one_draft_run(): void
    {
        $this->seed();
        $user = User::where('role', User::PAYROLL)->firstOrFail();
        $period = PayPeriod::current()->previous(3);

        $this->actingAs($user)->post('/payroll', ['period' => $period->key])->assertRedirect();

        $response = $this->actingAs($user)->post('/payroll', ['period' => $period->key]);

        $response->assertSessionHasErrors('period');
        $this->assertStringContainsString('already been run', session('errors')->first('period'));
        $this->assertSame(1, PayrollRun::where('period', $period->key)->where('status', PayrollRun::DRAFT)->count());
    }

    public function test_posting_the_current_month_that_already_has_a_draft_adds_no_record(): void
    {
        $this->seed();
        $user = User::where('role', User::PAYROLL)->firstOrFail();
        $period = PayPeriod::current();

        $this->assertSame(1, PayrollRun::where('period', $period->key)->count());

        $response = $this->actingAs($user)->post('/payroll', ['period' => $period->key]);

        $response->assertSessionHasErrors('period');
        $this->assertStringContainsString('already been run', session('errors')->first('period'));
        $this->assertSame(1, PayrollRun::where('period', $period->key)->count());
    }

    public function test_a_second_payroll_service_run_for_the_same_month_is_rejected(): void
    {
        $this->seed();
        $user = User::where('role', User::PAYROLL)->firstOrFail();
        $period = PayPeriod::current();

        try {
            app(PayrollService::class)->createRun($period, $user);
        } catch (\Throwable $e) {
            //
        }

        $this->assertSame(1, PayrollRun::where('period', $period->key)->count(), 'A duplicate payroll run was created for a month that already has one.');
    }

    public function test_a_raw_duplicate_payroll_run_for_the_same_month_is_rejected(): void
    {
        $this->seed();
        $user = User::where('role', User::PAYROLL)->firstOrFail();
        $period = PayPeriod::current();

        $this->expectException(QueryException::class);

        DB::table('payroll_runs')->insert([
            'period' => $period->key,
            'status' => PayrollRun::DRAFT,
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
