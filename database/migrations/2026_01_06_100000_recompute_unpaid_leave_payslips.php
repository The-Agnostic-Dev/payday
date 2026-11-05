<?php

use App\Services\PayrollService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(PayrollService::class)->recomputeUnpaidLeave();
    }

    public function down(): void
    {
        // Data migration; not reversible.
    }
};
