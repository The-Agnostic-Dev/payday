<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Grade;
use App\Services\LeaveCalendar;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public const DEPARTMENTS = ['Accounts', 'Admin', 'Logistics', 'Operations', 'Sales', 'Warehouse'];

    public function index()
    {
        return view('employees.index', [
            'employees' => Employee::with('grade')->orderBy('staff_no')->get(),
        ]);
    }

    public function show(Employee $employee, LeaveCalendar $calendar)
    {
        $employee->load(['grade', 'leaveRequests', 'payslips.run']);

        return view('employees.show', [
            'employee' => $employee,
            'leaveBalance' => $calendar->annualBalance($employee),
            'payslips' => $employee->payslips->sortByDesc(fn ($p) => $p->run->period),
        ]);
    }

    public function create()
    {
        return view('employees.form', [
            'employee' => new Employee(['staff_no' => $this->nextStaffNo()]),
            'grades' => Grade::orderBy('code')->get(),
            'departments' => self::DEPARTMENTS,
        ]);
    }

    public function store(Request $request)
    {
        $employee = Employee::create($this->validated($request));

        return redirect()->route('employees.show', $employee)->with('status', "{$employee->name} has been added.");
    }

    public function edit(Employee $employee)
    {
        return view('employees.form', [
            'employee' => $employee,
            'grades' => Grade::orderBy('code')->get(),
            'departments' => self::DEPARTMENTS,
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $employee->update($this->validated($request, $employee));

        return redirect()->route('employees.show', $employee)->with('status', 'Staff details saved.');
    }

    private function validated(Request $request, ?Employee $employee = null): array
    {
        $amount = ['nullable', 'string', 'max:20', 'regex:/^[₦\d,.\s]*$/u'];

        $data = $request->validate([
            'staff_no' => ['required', 'string', 'max:20', Rule::unique('employees')->ignore($employee)],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'department' => ['required', Rule::in(self::DEPARTMENTS)],
            'job_title' => ['required', 'string', 'max:80'],
            'grade_id' => ['required', 'exists:grades,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'bank_name' => ['nullable', 'string', 'max:60'],
            'account_number' => ['nullable', 'digits:10'],
            'loan_repayment' => $amount,
            'loan_balance' => $amount,
        ], [
            'loan_repayment.regex' => 'Enter the loan repayment as an amount in naira, e.g. 25,000.',
            'loan_balance.regex' => 'Enter the loan balance as an amount in naira, e.g. 150,000.',
            'account_number.digits' => 'NUBAN account numbers have 10 digits.',
        ]);

        $data['loan_repayment'] = Money::parse($data['loan_repayment'] ?? null);
        $data['loan_balance'] = Money::parse($data['loan_balance'] ?? null);

        return $data;
    }

    private function nextStaffNo(): string
    {
        $last = Employee::orderByDesc('staff_no')->value('staff_no');
        $number = $last ? ((int) substr($last, 3)) + 1 : 1;

        return sprintf('PD-%03d', $number);
    }
}
