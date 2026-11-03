<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use App\Models\PayrollRun;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    public function show(Request $request, Payslip $payslip)
    {
        $payslip->load(['employee.grade', 'run']);
        $user = $request->user();

        if ($user->isStaff()) {
            abort_unless(
                $payslip->employee_id === $user->employee_id && ! $payslip->run->isDraft(),
                404,
            );
        }

        return view('payslips.show', ['payslip' => $payslip]);
    }

    public function mine(Request $request)
    {
        $employee = $request->user()->employee;

        $payslips = $employee === null ? collect() : $employee->payslips()
            ->whereHas('run', fn ($q) => $q->where('status', PayrollRun::APPROVED))
            ->with('run')
            ->get()
            ->sortByDesc(fn (Payslip $p) => $p->run->period);

        return view('payslips.mine', ['employee' => $employee, 'payslips' => $payslips]);
    }
}
