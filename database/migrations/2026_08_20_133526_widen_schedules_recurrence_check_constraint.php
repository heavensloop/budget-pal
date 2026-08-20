<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Widen the allowed recurrence values so Savings items can use
        // Quarterly/Yearly schedules. Purely additive - no existing rows
        // use these values yet, so nothing needs backfilling.
        DB::statement('ALTER TABLE schedules DROP CONSTRAINT schedules_recurrence_check');
        DB::statement("ALTER TABLE schedules ADD CONSTRAINT schedules_recurrence_check CHECK (recurrence IN ('monthly', 'every_n_months', 'specific_months', 'quarterly', 'yearly'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE schedules DROP CONSTRAINT schedules_recurrence_check');
        DB::statement("ALTER TABLE schedules ADD CONSTRAINT schedules_recurrence_check CHECK (recurrence IN ('monthly', 'every_n_months', 'specific_months'))");
    }
};
