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
        // A Schedule now represents exactly one permanent recurring Need,
        // not a row-per-month carried forward by GenerateNextBudgetMonth
        // (removed) - so no two needs_items rows should ever share one.
        Schema::table('needs_items', function (Blueprint $table) {
            $table->unique('schedule_id');
        });

        // One-time items (schedule_id null) have no Schedule to key off,
        // so guard against duplicate names directly. Multiple NULLs are
        // fine in the unique index above, but a plain unique(user_id, name)
        // would also block a recurring and a one-time item sharing a name,
        // which is legitimate - so this is scoped to one-time items only.
        DB::statement(
            'CREATE UNIQUE INDEX needs_items_user_id_name_one_time_unique
                ON needs_items (user_id, name)
                WHERE schedule_id IS NULL',
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS needs_items_user_id_name_one_time_unique');

        Schema::table('needs_items', function (Blueprint $table) {
            $table->dropUnique(['schedule_id']);
        });
    }
};
