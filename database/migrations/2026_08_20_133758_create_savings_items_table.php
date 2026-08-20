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
        Schema::create('savings_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('name');
            $table->enum('type', ['savings', 'investment']);
            $table->decimal('target_amount', 14, 2);
            $table->decimal('installment_amount', 14, 2);
            $table->unsignedInteger('installments_made')->default(0);
            $table->decimal('target_profit', 14, 2)->nullable();
            $table->date('maturity_date')->nullable();
            $table->string('currency_code', 3);
            $table->enum('status', ['pending', 'ongoing', 'archived', 'completed'])->default('pending');
            $table->date('last_contribution_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // One-time items (schedule_id null) have no Schedule to key off, so
        // guard against duplicate names directly, scoped to one-time items
        // only. Mirrors debt_items_user_id_name_one_time_unique.
        DB::statement(
            'CREATE UNIQUE INDEX savings_items_user_id_name_one_time_unique
                ON savings_items (user_id, name)
                WHERE schedule_id IS NULL',
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('savings_items');
    }
};
