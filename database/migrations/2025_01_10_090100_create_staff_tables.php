<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // All money columns are whole kobo.
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedBigInteger('basic');
            $table->unsignedBigInteger('housing');
            $table->unsignedBigInteger('transport');
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('staff_no')->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('department');
            $table->string('job_title');
            $table->foreignId('grade_id')->constrained();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number', 10)->nullable();
            $table->unsignedBigInteger('basic')->default(0);
            $table->unsignedBigInteger('housing')->default(0);
            $table->unsignedBigInteger('transport')->default(0);
            $table->unsignedBigInteger('loan_repayment')->default(0);
            $table->unsignedBigInteger('loan_balance')->default(0);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->after('role')->constrained()->nullOnDelete();
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('days');
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('employee_id'));
        Schema::dropIfExists('employees');
        Schema::dropIfExists('grades');
    }
};
