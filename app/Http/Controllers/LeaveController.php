<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\LeaveCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveController extends Controller
{
    public function index(LeaveCalendar $calendar)
    {
        $employees = Employee::active()->with('leaveRequests')->orderBy('name')->get();

        return view('leave.index', [
            'leave' => LeaveRequest::with('employee')->orderByDesc('start_date')->get(),
            'employees' => $employees,
            'balances' => $employees->mapWithKeys(fn (Employee $e) => [$e->id => $calendar->annualBalance($e)]),
            'types' => LeaveRequest::TYPES,
        ]);
    }

    public function store(Request $request, LeaveCalendar $calendar)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'type' => ['required', Rule::in(array_keys(LeaveRequest::TYPES))],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'note' => ['nullable', 'string', 'max:200'],
        ], [
            'end_date.after' => 'The last day of leave must be after the first day.',
        ]);

        $start = CarbonImmutable::parse($data['start_date']);
        $end = CarbonImmutable::parse($data['end_date']);
        $data['days'] = $calendar->workingDays($start, $end);

        $leave = LeaveRequest::create($data);

        return redirect()->route('leave.index')
            ->with('status', "{$leave->typeLabel()} recorded for {$leave->employee->name} ({$leave->days} working days).");
    }

    public function destroy(LeaveRequest $leave)
    {
        $leave->delete();

        return redirect()->route('leave.index')->with('status', 'Leave removed.');
    }
}
