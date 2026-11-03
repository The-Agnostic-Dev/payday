@extends('layouts.app')

@section('title', 'Grades')

@section('content')
    <h1>Grades</h1>
    <p class="muted">Pay comes from the grade. A change here applies to everyone on the grade from the next payroll run; payroll already run is not changed.</p>

    <table class="table" data-testid="grades">
        <thead>
            <tr>
                <th>Grade</th><th>Name</th><th class="num">Basic</th><th class="num">Housing</th>
                <th class="num">Transport</th><th class="num">Monthly gross</th><th class="num">Staff</th><th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($grades as $grade)
                <tr data-grade="{{ $grade->code }}">
                    <td>{{ $grade->code }}</td>
                    <td>{{ $grade->name }}</td>
                    <td class="num">@naira($grade->basic)</td>
                    <td class="num">@naira($grade->housing)</td>
                    <td class="num">@naira($grade->transport)</td>
                    <td class="num gross">@naira($grade->monthlyGross())</td>
                    <td class="num">{{ $grade->employees_count }}</td>
                    <td><a href="{{ route('grades.edit', $grade) }}">Edit</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
