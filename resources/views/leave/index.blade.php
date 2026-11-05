@extends('layouts.app')

@section('title', 'Leave')

@section('content')
    <h1>Leave</h1>
    <p class="muted">
        Everyone gets {{ \App\Services\LeaveCalendar::ANNUAL_ENTITLEMENT }} working days of annual leave a calendar year.
        Annual and sick leave are paid. Unpaid leave is taken off pay for every working day it covers in the month.
    </p>

    <section class="card">
        <h2>Record leave</h2>
        <form method="POST" action="{{ route('leave.store') }}" class="form-grid">
            @csrf
            <label>Staff
                <select name="employee_id" required>
                    <option value="">Choose…</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected((int) old('employee_id') === $employee->id)>
                            {{ $employee->name }} ({{ $balances[$employee->id] }} days annual leave left)
                        </option>
                    @endforeach
                </select>
            </label>
            <label>Type
                <select name="type" required>
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>First day <input type="date" name="start_date" value="{{ old('start_date') }}" required></label>
            <label>Last day <input type="date" name="end_date" value="{{ old('end_date') }}" required></label>
            <label class="wide">Note <input name="note" value="{{ old('note') }}" maxlength="200"></label>
            <div class="actions"><button type="submit">Record leave</button></div>
        </form>
    </section>

    <table class="table" data-testid="leave">
        <thead>
            <tr><th>Staff</th><th>Type</th><th>From</th><th>To</th><th class="num">Working days</th><th>Note</th><th></th></tr>
        </thead>
        <tbody>
            @forelse ($leave as $item)
                <tr>
                    <td>{{ $item->employee->name }}</td>
                    <td>{{ $item->typeLabel() }}</td>
                    <td>{{ $item->start_date->format('D j M Y') }}</td>
                    <td>{{ $item->end_date->format('D j M Y') }}</td>
                    <td class="num">{{ $item->days }}</td>
                    <td>{{ $item->note }}</td>
                    <td>
                        <form method="POST" action="{{ route('leave.destroy', $item) }}" onsubmit="return confirm('Remove this leave?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="link danger">Remove</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">No leave recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
