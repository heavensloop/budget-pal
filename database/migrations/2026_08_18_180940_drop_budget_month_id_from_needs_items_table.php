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
        Schema::table('needs_items', function (Blueprint $table) {
            $table->dropForeign(['budget_month_id']);
            $table->dropColumn('budget_month_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('needs_items', function (Blueprint $table) {
            $table->foreignId('budget_month_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });
    }
};
