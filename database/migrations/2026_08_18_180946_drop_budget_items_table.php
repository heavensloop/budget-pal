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
        Schema::dropIfExists('budget_items');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_month_id')->constrained()->cascadeOnDelete();
            $table->morphs('source');
            $table->enum('type', ['needs', 'wants', 'debts', 'savings', 'incoming', 'additional']);
            $table->string('name');
            $table->decimal('amount', 14, 2);
            $table->string('currency_code', 3);
            $table->enum('status', ['pending', 'done', 'skipped']);
            $table->date('date_due')->nullable();
            $table->string('message')->nullable();
            $table->timestamps();

            $table->unique(['source_type', 'source_id']);
        });
    }
};
