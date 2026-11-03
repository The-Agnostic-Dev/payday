<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use Carbon\CarbonImmutable;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $today = CarbonImmutable::today();

        $activeStaff = Employee::query()->get();

        $latestRun = PayrollRun::withSum('payslips as net_total', 'net')
            ->withCount('payslips')
            ->orderByDesc('period')
            ->first();

        $onLeaveToday = LeaveRequest::with('employee')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->get();

        return view('dashboard', [
            'activeCount' => $activeStaff->count(),
            'wageBill' => $activeStaff->filter(fn (Employee $e) => ! $e->hasLeft())->sum(fn (Employee $e) => $e->monthlyGross()),
            'latestRun' => $latestRun,
            'onLeaveToday' => $onLeaveToday,
            'loanBook' => Employee::active()->sum('loan_balance'),
        ]);
    }
}
