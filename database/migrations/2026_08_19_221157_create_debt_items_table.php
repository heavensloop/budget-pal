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
        Schema::create('debt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('name');
            $table->enum('category', ['auto', 'personal', 'business', 'student', 'credit_card', 'mortgage', 'other']);
            $table->decimal('principal', 14, 2);
            $table->decimal('balance', 14, 2);
            $table->decimal('amount', 14, 2);
            $table->string('currency_code', 3);
            $table->enum('status', ['pending', 'archived'])->default('pending');
            $table->date('target_payoff_date')->nullable();
            $table->date('last_payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // One-time debts (schedule_id null) have no Schedule to key off, so
        // guard against duplicate names directly, scoped to one-time items
        // only - a recurring and a one-time debt may legitimately share a
        // name. Mirrors needs_items_user_id_name_one_time_unique.
        DB::statement(
            'CREATE UNIQUE INDEX debt_items_user_id_name_one_time_unique
                ON debt_items (user_id, name)
                WHERE schedule_id IS NULL',
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debt_items');
    }
};
