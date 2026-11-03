@extends('layouts.app')

@section('title', 'Payroll '.$run->payPeriod()->label())

@section('content')
    <div class="page-head">
        <div>
            <h1>Payroll for {{ $run->payPeriod()->label() }}</h1>
            <p class="muted">
                <span class="badge {{ $run->status }}">{{ ucfirst($run->status) }}</span>
                Run by {{ $run->creator?->name ?? 'Payday' }} on {{ $run->created_at->format('j M Y, g:ia') }}
                @if ($run->approved_at) · approved {{ $run->approved_at->format('j M Y') }} @endif
            </p>
        </div>
        <div class="actions">
            <a class="button secondary" href="{{ route('payroll.export', $run) }}">Download summary (CSV)</a>
            @if ($run->isDraft())
                @if (auth()->user()->hasRole('owner'))
                    <form method="POST" action="{{ route('payroll.approve', $run) }}">
                        @csrf
                        <button type="submit">Approve payroll</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('payroll.destroy', $run) }}" onsubmit="return confirm('Discard this draft?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="danger">Discard draft</button>
                </form>
            @endif
        </div>
    </div>

    <div class="tiles">
        <div class="tile"><span class="label">Staff paid</span><strong class="value">{{ $payslips->count() }}</strong></div>
        <div class="tile"><span class="label">Gross pay</span><strong class="value">@naira($payslips->sum('gross'))</strong></div>
        <div class="tile"><span class="label">Deductions</span><strong class="value">@naira($payslips->sum(fn ($p) => $p->totalDeductions()))</strong></div>
    </div>

    <table class="table" data-testid="payslips">
        <thead>
            <tr>
                <th>Staff</th><th class="num">Days paid</th><th class="num">Gross</th><th class="num">Pension</th>
                <th class="num">Tax</th><th class="num">Loan</th><th class="num">Net pay</th><th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($payslips as $payslip)
                <tr data-staff="{{ $payslip->employee->name }}">
                    <td>{{ $payslip->employee->name }} <small class="muted">{{ $payslip->employee->staff_no }}</small></td>
                    <td class="num days">{{ $payslip->days_paid }}/{{ $payslip->days_in_month }}</td>
                    <td class="num gross">@naira($payslip->gross)</td>
                    <td class="num pension">@naira($payslip->pension)</td>
                    <td class="num tax">@naira($payslip->tax)</td>
                    <td class="num loan">@naira($payslip->loan)</td>
                    <td class="num net">@naira($payslip->net)</td>
                    <td><a href="{{ route('payslips.show', $payslip->employee_id) }}">Payslip</a></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th>Total</th>
                <th></th>
                <th class="num">@naira($payslips->sum('gross'))</th>
                <th class="num">@naira($payslips->sum('pension'))</th>
                <th class="num">@naira($payslips->sum('tax'))</th>
                <th class="num">@naira($payslips->sum('loan'))</th>
                <th class="num net-total">@naira($payslips->sum('gross'))</th>
                <th></th>
            </tr>
        </tfoot>
    </table>
@endsection
