@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h1>Good day, {{ strtok(auth()->user()->name, ' ') }}</h1>

    <div class="tiles">
        <div class="tile" data-testid="active-staff">
            <span class="label">Active staff</span>
            <strong class="value">{{ $activeCount }}</strong>
        </div>
        <div class="tile">
            <span class="label">Monthly wage bill (gross)</span>
            <strong class="value">@naira($wageBill)</strong>
        </div>
        <div class="tile">
            <span class="label">Staff loans outstanding</span>
            <strong class="value">@naira($loanBook)</strong>
        </div>
    </div>

    <div class="grid-2">
        <section class="card">
            <h2>Latest payroll</h2>
            @if ($latestRun)
                <p>
                    <a href="{{ route('payroll.show', $latestRun) }}">{{ $latestRun->payPeriod()->label() }}</a>
                    <span class="badge {{ $latestRun->status }}">{{ ucfirst($latestRun->status) }}</span>
                </p>
                <p>{{ $latestRun->payslips_count }} payslips, net pay @naira($latestRun->net_total ?? 0)</p>
            @else
                <p class="muted">No payroll has been run yet.</p>
            @endif
            <a class="button" href="{{ route('payroll.index') }}">Go to payroll</a>
        </section>

        <section class="card">
            <h2>On leave today</h2>
            @forelse ($onLeaveToday as $leave)
                <p>{{ $leave->employee->name }} <span class="muted">{{ $leave->typeLabel() }}, back after {{ $leave->end_date->format('j M') }}</span></p>
            @empty
                <p class="muted">Everyone is in today.</p>
            @endforelse
        </section>
    </div>
@endsection
