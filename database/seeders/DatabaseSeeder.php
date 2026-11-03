<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Grade;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\LeaveCalendar;
use App\Services\PayPeriod;
use App\Services\PayrollService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Demo data for Adunni Foods Ltd. Dates are relative to today so the demo
 * always has two approved months and a draft for the current month.
 */
class DatabaseSeeder extends Seeder
{
    public const PASSWORD = 'payday2026';

    public function run(PayrollService $payroll, LeaveCalendar $calendar): void
    {
        $current = PayPeriod::current();
        $last = $current->previous();
        $before = $current->previous(2);

        $grades = collect([
            ['G1', 'Junior staff', 70_000, 20_000, 10_000],
            ['G2', 'Officer', 120_000, 40_000, 20_000],
            ['G3', 'Senior officer', 200_000, 70_000, 30_000],
            ['G4', 'Manager', 350_000, 120_000, 50_000],
            ['G5', 'Head of department', 550_000, 200_000, 80_000],
        ])->mapWithKeys(fn ($g) => [$g[0] => Grade::create([
            'code' => $g[0], 'name' => $g[1], 'basic' => $g[2] * 100, 'housing' => $g[3] * 100, 'transport' => $g[4] * 100,
        ])]);

        $staff = [
            ['PD-001', 'Bisi Adeyemi', 'Admin', 'HR & Payroll Officer', 'G3', '2021-03-01', null, 'GTBank', '0123456781'],
            ['PD-002', 'Emeka Nwosu', 'Accounts', 'Accountant', 'G4', '2020-06-15', null, 'Access Bank', '0698765432'],
            ['PD-003', 'Tunde Bakare', 'Sales', 'Sales Manager', 'G4', '2019-01-07', null, 'Zenith Bank', '2087654321'],
            ['PD-004', 'Adaeze Okonkwo', 'Sales', 'Sales Officer', 'G2', '2022-09-01', null, 'UBA', '2034567890'],
            ['PD-005', 'Chinedu Eze', 'Warehouse', 'Warehouse Supervisor', 'G3', '2020-11-02', null, 'First Bank', '3045678912', 25_000, 110_000],
            ['PD-006', 'Ngozi Okafor', 'Logistics', 'Dispatch Officer', 'G2', '2023-02-13', null, 'Fidelity Bank', '6012345678'],
            ['PD-007', 'Musa Ibrahim', 'Logistics', 'Driver', 'G1', '2021-08-16', null, 'Opay', '8031234567'],
            ['PD-008', 'Yetunde Alabi', 'Accounts', 'Accounts Assistant', 'G2', '2022-04-04', null, 'GTBank', '0156789012', 20_000, 52_000],
            ['PD-009', 'Ibrahim Sule', 'Warehouse', 'Storekeeper', 'G1', '2023-07-03', null, 'Moniepoint', '5023456789'],
            ['PD-010', 'Funmi Ogunleye', 'Sales', 'Sales Rep', 'G1', '2024-01-08', null, 'Wema Bank', '0234567812'],
            ['PD-011', 'Segun Adebayo', 'Logistics', 'Driver', 'G1', '2022-05-09', $last->start->addDays(14)->toDateString(), 'Polaris Bank', '1765432109'],
            ['PD-012', 'Halima Danjuma', 'Admin', 'Front Desk Officer', 'G1', '2024-10-01', null, 'Stanbic IBTC', '0045678123'],
            ['PD-013', 'Kelechi Nnaji', 'Sales', 'Sales Rep', 'G1', $current->start->addDays(9)->toDateString(), null, 'Access Bank', '0712345689'],
            ['PD-014', 'Oluwaseun Martins', 'Operations', 'Head of Operations', 'G5', '2018-09-03', null, 'Zenith Bank', '2112345678', 50_000, 500_000],
            ['PD-015', 'Grace Etim', 'Warehouse', 'Packer', 'G1', $last->start->addDays(17)->toDateString(), null, 'Kuda', '2009876543'],
        ];

        $employees = collect($staff)->mapWithKeys(function (array $s) use ($grades) {
            [$no, $name, $dept, $title, $grade, $start, $end, $bank, $account] = $s;

            return [$no => Employee::create([
                'staff_no' => $no,
                'name' => $name,
                'email' => strtolower(strtok($name, ' ')).'@adunnifoods.ng',
                'phone' => '080'.substr($account, 2, 8),
                'department' => $dept,
                'job_title' => $title,
                'grade_id' => $grades[$grade]->id,
                'start_date' => $start,
                'end_date' => $end,
                'bank_name' => $bank,
                'account_number' => $account,
                'loan_repayment' => ($s[9] ?? 0) * 100,
                'loan_balance' => ($s[10] ?? 0) * 100,
            ])];
        });

        $this->seedLeave($employees, $calendar, $current, $last);

        User::create(['name' => 'Bisi Adeyemi', 'email' => 'bisi@adunnifoods.ng', 'password' => self::PASSWORD,
            'role' => User::PAYROLL, 'employee_id' => $employees['PD-001']->id]);
        User::create(['name' => 'Folake Adunni-Bello', 'email' => 'folake@adunnifoods.ng', 'password' => self::PASSWORD,
            'role' => User::OWNER]);
        User::create(['name' => 'Adaeze Okonkwo', 'email' => 'adaeze@adunnifoods.ng', 'password' => self::PASSWORD,
            'role' => User::STAFF, 'employee_id' => $employees['PD-004']->id]);

        $bisi = User::where('role', User::PAYROLL)->first();

        foreach ([$before, $last] as $period) {
            $run = $payroll->createRun($period, $bisi);
            $run->forceFill(['created_at' => $period->end->subDays(3)->setTime(10, 0)])->save();
            $payroll->approve($run);
            $run->forceFill(['approved_at' => $period->end->subDays(2)->setTime(15, 30)])->save();
        }

        $payroll->createRun($current, $bisi);
    }

    private function seedLeave($employees, LeaveCalendar $calendar, PayPeriod $current, PayPeriod $last): void
    {
        $friday = $current->start->addDays(2);
        while (! $friday->isFriday()) {
            $friday = $friday->addDay();
        }

        $today = CarbonImmutable::today();

        $leave = [
            ['PD-010', LeaveRequest::UNPAID, $friday, $friday->addDays(3), 'Family event in Ibadan'],
            ['PD-004', LeaveRequest::ANNUAL, $last->start->addDays(6), $last->start->addDays(10), null],
            ['PD-003', LeaveRequest::ANNUAL, $today->subDay(), $today->addDays(2), 'Travelling to Abuja'],
            ['PD-007', LeaveRequest::SICK, $last->start->addDays(20), $last->start->addDays(21), 'Malaria'],
        ];

        foreach ($leave as [$no, $type, $start, $end, $note]) {
            LeaveRequest::create([
                'employee_id' => $employees[$no]->id,
                'type' => $type,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'days' => $calendar->workingDays($start, $end),
                'note' => $note,
            ]);
        }
    }
}
