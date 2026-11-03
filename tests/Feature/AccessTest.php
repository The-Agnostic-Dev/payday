<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PayPeriod;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Log in');
    }

    public function test_logging_in(): void
    {
        $user = $this->user();

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-password'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_staff_only_see_their_own_approved_payslips(): void
    {
        $mine = $this->employee(['name' => 'Me']);
        $other = $this->employee(['name' => 'Someone else']);
        $service = app(PayrollService::class);
        $run = $service->createRun(PayPeriod::fromKey('2026-03'));
        $staff = $this->user(User::STAFF, $mine);

        $myslip = $run->payslips()->where('employee_id', $mine->id)->first();
        $theirs = $run->payslips()->where('employee_id', $other->id)->first();

        $this->actingAs($staff)->get("/payslips/{$myslip->id}")->assertNotFound();

        $service->approve($run);

        $this->actingAs($staff)->get("/payslips/{$myslip->id}")->assertOk()->assertSee('Me');
        $this->actingAs($staff)->get("/payslips/{$theirs->id}")->assertNotFound();
        $this->actingAs($staff)->get('/my/payslips')->assertOk()->assertSee('March 2026');
        $this->actingAs($staff)->get('/dashboard')->assertForbidden();
    }

    public function test_main_screens_load(): void
    {
        $employee = $this->employee();
        app(PayrollService::class)->createRun(PayPeriod::fromKey('2026-03'));
        $user = $this->user(User::OWNER);

        foreach (['/dashboard', '/staff', "/staff/{$employee->id}", "/staff/{$employee->id}/edit", '/staff/new', '/grades', '/leave', '/payroll'] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }
}
