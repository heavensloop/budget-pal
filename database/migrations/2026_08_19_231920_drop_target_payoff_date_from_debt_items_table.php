<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Redundant with the Schedule's own start_date/end_date range.
        Schema::table('debt_items', function (Blueprint $table) {
            $table->dropColumn('target_payoff_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('debt_items', function (Blueprint $table) {
            $table->date('target_payoff_date')->nullable();
        });
    }
};
