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
        Schema::create('income_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('name');
            $table->enum('category', ['salary', 'freelance', 'business', 'gift', 'allowance', 'royalty', 'investment', 'other']);
            $table->decimal('amount', 14, 2);
            $table->string('currency_code', 3);
            $table->enum('status', ['pending', 'archived'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // One-time income items (schedule_id null) have no Schedule to key
        // off, so guard against duplicate names directly, scoped to
        // one-time items only. Mirrors needs_items/debt_items.
        DB::statement(
            'CREATE UNIQUE INDEX income_items_user_id_name_one_time_unique
                ON income_items (user_id, name)
                WHERE schedule_id IS NULL',
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('income_items');
    }
};
