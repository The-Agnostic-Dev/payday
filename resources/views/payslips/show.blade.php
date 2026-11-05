@extends('layouts.app')

@section('title', 'Payslip '.$payslip->employee->name)

@section('content')
    @php($employee = $payslip->employee)
    <article class="payslip card" data-testid="payslip">
        <header class="payslip-head">
            <div>
                <strong>{{ config('app.company.name') }}</strong><br>
                <span class="muted">{{ config('app.company.address') }}</span>
            </div>
            <div class="right">
                <h1>Payslip</h1>
                <span class="period">{{ $payslip->run->payPeriod()->label() }}</span>
                @if ($payslip->run->isDraft()) <span class="badge draft">Draft</span> @endif
            </div>
        </header>

        <dl class="pairs">
            <dt>Name</dt><dd class="employee-name">{{ $employee->name }}</dd>
            <dt>Staff no</dt><dd>{{ $employee->staff_no }}</dd>
            <dt>Job title</dt><dd>{{ $employee->job_title }}, {{ $employee->department }}</dd>
            <dt>Grade</dt><dd>{{ $employee->grade->code }}</dd>
            <dt>Bank</dt><dd>{{ $employee->bank_name }} {{ $employee->account_number }}</dd>
            <dt>Days paid</dt><dd class="days">{{ $payslip->days_paid }} of {{ $payslip->days_in_month }}</dd>
            @if ($payslip->unpaid_leave_days)
                <dt>Unpaid leave</dt><dd>{{ $payslip->unpaid_leave_days }} working days</dd>
            @endif
        </dl>

        <div class="grid-2">
            <table class="table">
                <thead><tr><th>Earnings</th><th class="num">₦</th></tr></thead>
                <tbody>
                    <tr><td>Basic</td><td class="num">@naira($payslip->basic)</td></tr>
                    <tr><td>Housing allowance</td><td class="num">@naira($payslip->housing)</td></tr>
                    <tr><td>Transport allowance</td><td class="num">@naira($payslip->transport)</td></tr>
                </tbody>
                <tfoot><tr><th>Gross pay</th><th class="num gross">@naira($payslip->gross)</th></tr></tfoot>
            </table>

            <table class="table">
                <thead><tr><th>Deductions</th><th class="num">₦</th></tr></thead>
                <tbody>
                    <tr><td>Pension (8%)</td><td class="num">@naira($payslip->pension)</td></tr>
                    <tr><td>Income tax (PAYE)</td><td class="num">@naira($payslip->tax)</td></tr>
                    <tr><td>Staff loan</td><td class="num">@naira($payslip->loan)</td></tr>
                </tbody>
                <tfoot><tr><th>Total deductions</th><th class="num deductions">@naira($payslip->totalDeductions())</th></tr></tfoot>
            </table>
        </div>

        <p class="net-pay">Net pay <strong class="net">@naira($payslip->net)</strong></p>
    </article>

    <p class="no-print"><button type="button" onclick="window.print()">Print payslip</button></p>
@endsection
