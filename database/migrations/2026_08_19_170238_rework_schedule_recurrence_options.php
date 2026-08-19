<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('schedules')->whereIn('recurrence', ['weekly', 'biweekly', 'yearly'])->delete();

        DB::statement('ALTER TABLE schedules DROP CONSTRAINT schedules_recurrence_check');
        DB::statement("ALTER TABLE schedules ADD CONSTRAINT schedules_recurrence_check CHECK (recurrence IN ('monthly', 'every_n_months', 'specific_months'))");

        Schema::table('schedules', function (Blueprint $table) {
            $table->unsignedTinyInteger('interval_months')->nullable();
            $table->json('months')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['interval_months', 'months']);
        });

        DB::statement('ALTER TABLE schedules DROP CONSTRAINT schedules_recurrence_check');
        DB::statement("ALTER TABLE schedules ADD CONSTRAINT schedules_recurrence_check CHECK (recurrence IN ('monthly', 'weekly', 'biweekly', 'yearly'))");
    }
};
