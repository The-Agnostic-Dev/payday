@extends('layouts.app')

@section('title', $employee->exists ? 'Edit '.$employee->name : 'Add staff')

@section('content')
    <h1>{{ $employee->exists ? 'Edit '.$employee->name : 'Add staff' }}</h1>

    <form method="POST" class="card form-grid"
          action="{{ $employee->exists ? route('employees.update', $employee) : route('employees.store') }}">
        @csrf
        @if ($employee->exists) @method('PUT') @endif

        <label>Staff no <input name="staff_no" value="{{ old('staff_no', $employee->staff_no) }}" required></label>
        <label>Full name <input name="name" value="{{ old('name', $employee->name) }}" required></label>
        <label>Email <input type="email" name="email" value="{{ old('email', $employee->email) }}"></label>
        <label>Phone <input name="phone" value="{{ old('phone', $employee->phone) }}"></label>

        <label>Department
            <select name="department" required>
                @foreach ($departments as $department)
                    <option @selected(old('department', $employee->department) === $department)>{{ $department }}</option>
                @endforeach
            </select>
        </label>
        <label>Job title <input name="job_title" value="{{ old('job_title', $employee->job_title) }}" required></label>

        <label>Grade
            <select name="grade_id" required>
                @foreach ($grades as $grade)
                    <option value="{{ $grade->id }}" @selected((int) old('grade_id', $employee->grade_id) === $grade->id)>
                        {{ $grade->code }}, {{ $grade->name }} (@naira($grade->monthlyGross()) a month)
                    </option>
                @endforeach
            </select>
        </label>
        <span></span>

        <label>Start date <input type="date" name="start_date" value="{{ old('start_date', $employee->start_date?->format('Y-m-d')) }}" required></label>
        <label>Leaving date <input type="date" name="end_date" value="{{ old('end_date', $employee->end_date?->format('Y-m-d')) }}"></label>

        <label>Bank <input name="bank_name" value="{{ old('bank_name', $employee->bank_name) }}"></label>
        <label>Account number <input name="account_number" inputmode="numeric" value="{{ old('account_number', $employee->account_number) }}"></label>

        <label>Loan repayment a month (₦)
            <input name="loan_repayment" inputmode="decimal" value="{{ old('loan_repayment', \App\Support\Money::forInput($employee->loan_repayment ?? 0)) }}">
        </label>
        <label>Loan balance (₦)
            <input name="loan_balance" inputmode="decimal" value="{{ old('loan_balance', \App\Support\Money::forInput($employee->loan_balance ?? 0)) }}">
        </label>

        <div class="actions">
            <button type="submit">Save</button>
            <a href="{{ $employee->exists ? route('employees.show', $employee) : route('employees.index') }}">Cancel</a>
        </div>
    </form>
@endsection
