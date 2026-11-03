@extends('layouts.app')

@section('title', 'My payslips')

@section('content')
    <h1>My payslips</h1>

    @if ($employee === null)
        <p class="muted">Your login is not linked to a staff record yet. Please speak to HR.</p>
    @else
        <p class="muted">{{ $employee->name }} · {{ $employee->staff_no }}. Payslips appear here once the month's payroll has been approved.</p>

        <table class="table" data-testid="my-payslips">
            <thead><tr><th>Month</th><th class="num">Gross</th><th class="num">Deductions</th><th class="num">Net pay</th><th></th></tr></thead>
            <tbody>
                @forelse ($payslips as $payslip)
                    <tr>
                        <td>{{ $payslip->run->payPeriod()->label() }}</td>
                        <td class="num">@naira($payslip->gross)</td>
                        <td class="num">@naira($payslip->totalDeductions())</td>
                        <td class="num">@naira($payslip->net)</td>
                        <td><a href="{{ route('payslips.show', $payslip) }}">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No approved payslips yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif
@endsection
