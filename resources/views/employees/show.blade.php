@extends('layouts.app')

@section('title', $employee->name)

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ $employee->name }}</h1>
            <p class="muted">{{ $employee->staff_no }} · {{ $employee->job_title }}, {{ $employee->department }} · {{ $employee->statusLabel() }}</p>
        </div>
        <a class="button" href="{{ route('employees.edit', $employee) }}">Edit details</a>
    </div>

    <div class="grid-2">
        <section class="card">
            <h2>Pay</h2>
            <dl class="pairs" data-testid="pay">
                <dt>Grade</dt><dd>{{ $employee->grade->code }}, {{ $employee->grade->name }}</dd>
                <dt>Basic</dt><dd>@naira($employee->basic)</dd>
                <dt>Housing allowance</dt><dd>@naira($employee->housing)</dd>
                <dt>Transport allowance</dt><dd>@naira($employee->transport)</dd>
                <dt>Monthly gross</dt><dd class="gross">@naira($employee->monthlyGross())</dd>
                <dt>Loan repayment</dt><dd class="loan-repayment">@naira($employee->loan_repayment) a month</dd>
                <dt>Loan balance</dt><dd class="loan-balance">@naira($employee->loan_balance)</dd>
            </dl>
        </section>

        <section class="card">
            <h2>Details</h2>
            <dl class="pairs">
                <dt>Started</dt><dd>{{ $employee->start_date->format('j F Y') }}</dd>
                <dt>Leaving date</dt><dd>{{ $employee->end_date?->format('j F Y') ?? 'None' }}</dd>
                <dt>Email</dt><dd>{{ $employee->email ?? 'None' }}</dd>
                <dt>Phone</dt><dd>{{ $employee->phone ?? 'None' }}</dd>
                <dt>Bank</dt><dd>{{ $employee->bank_name ?? 'None' }} {{ $employee->account_number }}</dd>
                <dt>Annual leave left</dt><dd>{{ $leaveBalance }} of {{ \App\Services\LeaveCalendar::ANNUAL_ENTITLEMENT }} days</dd>
            </dl>
        </section>
    </div>

    <section class="card">
        <h2>Payslips</h2>
        <table class="table">
            <thead><tr><th>Month</th><th>Status</th><th class="num">Gross</th><th class="num">Net pay</th><th></th></tr></thead>
            <tbody>
                @forelse ($payslips as $payslip)
                    <tr>
                        <td>{{ $payslip->run->payPeriod()->label() }}</td>
                        <td><span class="badge {{ $payslip->run->status }}">{{ ucfirst($payslip->run->status) }}</span></td>
                        <td class="num">@naira($payslip->gross)</td>
                        <td class="num">@naira($payslip->net)</td>
                        <td><a href="{{ route('payslips.show', $payslip) }}">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No payslips yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="card">
        <h2>Leave</h2>
        <table class="table">
            <thead><tr><th>Type</th><th>From</th><th>To</th><th class="num">Working days</th><th>Note</th></tr></thead>
            <tbody>
                @forelse ($employee->leaveRequests as $leave)
                    <tr>
                        <td>{{ $leave->typeLabel() }}</td>
                        <td>{{ $leave->start_date->format('D j M Y') }}</td>
                        <td>{{ $leave->end_date->format('D j M Y') }}</td>
                        <td class="num">{{ $leave->days }}</td>
                        <td>{{ $leave->note }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No leave recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection
