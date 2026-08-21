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
        Schema::create('want_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->enum('category', ['electronics', 'clothing', 'entertainment', 'gifts', 'hobbies', 'travel', 'food_and_dining', 'health_and_fitness', 'other']);
            $table->decimal('amount', 14, 2);
            $table->string('currency_code', 3);
            $table->enum('status', ['planned', 'purchased', 'archived'])->default('planned');
            $table->unsignedInteger('position');
            $table->date('purchased_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('want_items');
    }
};
