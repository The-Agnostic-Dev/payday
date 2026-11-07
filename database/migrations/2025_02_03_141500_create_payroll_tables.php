<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->string('period', 7)->unique();
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        // Amounts are whole kobo, frozen at the time the run was made.
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained();
            $table->unsignedTinyInteger('days_in_month');
            $table->unsignedTinyInteger('days_paid');
            $table->unsignedTinyInteger('unpaid_leave_days')->default(0);
            $table->unsignedBigInteger('basic');
            $table->unsignedBigInteger('housing');
            $table->unsignedBigInteger('transport');
            $table->unsignedBigInteger('gross');
            $table->unsignedBigInteger('pension');
            $table->unsignedBigInteger('tax');
            $table->unsignedBigInteger('loan');
            $table->bigInteger('net');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_runs');
    }
};
