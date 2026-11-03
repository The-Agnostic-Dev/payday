@extends('layouts.app')

@section('title', 'Staff')

@section('content')
    <div class="page-head">
        <h1>Staff</h1>
        <a class="button" href="{{ route('employees.create') }}">Add staff</a>
    </div>

    <div class="toolbar">
        <input type="search" id="staff-filter" placeholder="Find staff by name, staff no or department" aria-label="Find staff">
        <span class="muted" id="staff-count">{{ $employees->count() }} staff</span>
    </div>

    <table class="table" data-testid="staff">
        <thead>
            <tr>
                <th>Staff no</th>
                <th>Name</th>
                <th>Department</th>
                <th>Grade</th>
                <th class="num">Monthly gross</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($employees as $employee)
                <tr data-name="{{ Str::lower($employee->name) }}" data-staff-no="{{ $employee->staff_no }}" data-department="{{ $employee->department }}">
                    <td>{{ $employee->staff_no }}</td>
                    <td class="name"><a href="{{ route('employees.show', $employee) }}">{{ $employee->name }}</a></td>
                    <td>{{ $employee->department }}</td>
                    <td class="grade">{{ $employee->grade->code }}</td>
                    <td class="num gross">@naira($employee->monthlyGross())</td>
                    <td class="status">{{ $employee->statusLabel() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="muted" id="staff-empty" hidden>No staff match that search.</p>
@endsection

@push('scripts')
    <script src="{{ asset('js/staff-filter.js') }}"></script>
@endpush
