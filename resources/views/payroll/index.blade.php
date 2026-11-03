@extends('layouts.app')

@section('title', 'Payroll')

@section('content')
    <h1>Payroll</h1>

    <section class="card">
        <h2>Run payroll</h2>
        <p class="muted">Makes a draft with a payslip for everyone on the books that month. Nothing is paid until the owner approves it.</p>
        <form method="POST" action="{{ route('payroll.store') }}" class="inline-form">
            @csrf
            <label>Month <input type="month" name="period" value="{{ old('period', $suggested->key) }}" required></label>
            <button type="submit">Run payroll</button>
        </form>
    </section>

    <table class="table" data-testid="runs">
        <thead>
            <tr><th>Month</th><th>Status</th><th class="num">Payslips</th><th class="num">Gross</th><th class="num">Net pay</th><th></th></tr>
        </thead>
        <tbody>
            @forelse ($runs as $run)
                <tr data-period="{{ $run->period }}">
                    <td>{{ $run->payPeriod()->label() }}</td>
                    <td><span class="badge {{ $run->status }}">{{ ucfirst($run->status) }}</span></td>
                    <td class="num">{{ $run->payslips_count }}</td>
                    <td class="num">@naira($run->gross_total ?? 0)</td>
                    <td class="num">@naira($run->net_total ?? 0)</td>
                    <td><a href="{{ route('payroll.show', $run) }}">Open</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">No payroll yet.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
