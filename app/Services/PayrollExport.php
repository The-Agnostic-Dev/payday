<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollRun;
use App\Support\Money;

/**
 * The payroll summary the owner sends to the bank and the accountant.
 */
class PayrollExport
{
    public const HEADINGS = [
        'Staff no', 'Name', 'Department', 'Bank', 'Account number', 'Days paid',
        'Basic', 'Housing', 'Transport', 'Gross', 'Pension', 'Tax', 'Loan', 'Net pay',
    ];

    public function filename(PayrollRun $run): string
    {
        return 'payroll-summary-'.$run->period.'.csv';
    }

    /** @return list<list<string>> */
    public function rows(PayrollRun $run): array
    {
        $payslips = $run->payslips()->get()->keyBy('employee_id');
        $rows = [];
        $totals = array_fill_keys(['basic', 'housing', 'transport', 'gross', 'pension', 'tax', 'loan', 'net'], 0);

        foreach (Employee::active()->orderBy('name')->get() as $employee) {
            $payslip = $payslips->get($employee->id);

            if ($payslip === null) {
                continue;
            }

            foreach ($totals as $key => $sum) {
                $totals[$key] = $sum + $payslip->{$key};
            }

            $rows[] = [
                $employee->staff_no,
                $employee->name,
                $employee->department,
                (string) $employee->bank_name,
                (string) $employee->account_number,
                $payslip->days_paid.'/'.$payslip->days_in_month,
                ...array_map(fn (string $key) => Money::plain($payslip->{$key}), array_keys($totals)),
            ];
        }

        $rows[] = ['', 'TOTAL', '', '', '', '', ...array_map(fn (int $sum) => Money::plain($sum), array_values($totals))];

        return $rows;
    }

    public function toCsv(PayrollRun $run): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, self::HEADINGS, escape: '');

        foreach ($this->rows($run) as $row) {
            fputcsv($handle, $row, escape: '');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}
