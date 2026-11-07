<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('payroll_runs as run')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('payroll_runs as keep')
                    ->whereColumn('keep.period', 'run.period')
                    ->whereColumn('keep.id', '<', 'run.id');
            })
            ->pluck('run.id');

        if ($duplicates->isNotEmpty()) {
            DB::table('payslips')->whereIn('payroll_run_id', $duplicates)->delete();
            DB::table('payroll_runs')->whereIn('id', $duplicates)->delete();
        }

        Schema::table('payroll_runs', fn (Blueprint $table) => $table->unique('period'));
    }

    public function down(): void
    {
        Schema::table('payroll_runs', fn (Blueprint $table) => $table->dropUnique('payroll_runs_period_unique'));
    }
};
