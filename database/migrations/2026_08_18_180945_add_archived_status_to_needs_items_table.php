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
        DB::statement('ALTER TABLE needs_items DROP CONSTRAINT needs_items_status_check');
        DB::statement("ALTER TABLE needs_items ADD CONSTRAINT needs_items_status_check CHECK (status IN ('pending', 'done', 'skipped', 'archived'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE needs_items DROP CONSTRAINT needs_items_status_check');
        DB::statement("ALTER TABLE needs_items ADD CONSTRAINT needs_items_status_check CHECK (status IN ('pending', 'done', 'skipped'))");
    }
};
