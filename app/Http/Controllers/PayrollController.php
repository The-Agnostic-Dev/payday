<?php

namespace App\Http\Controllers;

use App\Models\PayrollRun;
use App\Services\PayPeriod;
use App\Services\PayrollExport;
use App\Services\PayrollService;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function __construct(private readonly PayrollService $payroll) {}

    public function index()
    {
        $runs = PayrollRun::withCount('payslips')
            ->withSum('payslips as gross_total', 'gross')
            ->withSum('payslips as net_total', 'net')
            ->orderByDesc('period')
            ->get();

        $suggested = PayPeriod::current();
        while ($this->payroll->hasRun($suggested)) {
            $suggested = $suggested->next();
        }

        return view('payroll.index', ['runs' => $runs, 'suggested' => $suggested]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'period' => ['required', 'date_format:Y-m'],
        ], [
            'period.date_format' => 'Pick the month to run payroll for.',
        ]);

        $period = PayPeriod::fromKey($data['period']);

        if ($this->payroll->hasRun($period)) {
            return back()->withErrors(['period' => "Payroll for {$period->label()} has already been run."]);
        }

        $run = $this->payroll->createRun($period, $request->user());

        return redirect()->route('payroll.show', $run)
            ->with('status', "Draft payroll for {$period->label()} is ready. Check it before the owner approves.");
    }

    public function show(PayrollRun $run)
    {
        $run->load(['payslips.employee', 'creator']);

        return view('payroll.show', [
            'run' => $run,
            'payslips' => $run->payslips->sortBy(fn ($p) => $p->employee->name)->values(),
        ]);
    }

    public function export(PayrollRun $run, PayrollExport $export)
    {
        return response($export->toCsv($run), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$export->filename($run).'"',
        ]);
    }

    public function approve(PayrollRun $run)
    {
        abort_unless($run->isDraft(), 422, 'This payroll has already been approved.');

        $this->payroll->approve($run);

        return redirect()->route('payroll.show', $run)->with('status', 'Payroll approved. Staff can now see their payslips.');
    }

    public function destroy(PayrollRun $run)
    {
        abort_unless($run->isDraft(), 422, 'Approved payroll cannot be discarded.');

        $label = $run->payPeriod()->label();
        $this->payroll->discard($run);

        return redirect()->route('payroll.index')->with('status', "Draft payroll for {$label} discarded.");
    }
}
