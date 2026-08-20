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
        Schema::table('debt_items', function (Blueprint $table) {
            $table->decimal('amount_borrowed', 14, 2)->nullable()->after('category');
            $table->decimal('total_repayment_amount', 14, 2)->nullable()->after('amount_borrowed');
            $table->decimal('monthly_repayment_amount', 14, 2)->nullable()->after('total_repayment_amount');
            $table->unsignedInteger('tenure_months')->nullable()->after('monthly_repayment_amount');
            $table->unsignedInteger('payments_made')->default(0)->after('tenure_months');
        });

        // Backfill existing rows from the old principal/balance/amount shape.
        // We have no record of real total repayment or tenure, so this
        // assumes 0 interest (total repayment = amount borrowed) and 0
        // payments made so far - the safest non-fabricated starting point.
        // Tenure is derived as how many payments at the existing monthly
        // amount would repay the original principal. Review/edit each
        // debt afterward to set its real total repayment amount and tenure.
        foreach (DB::table('debt_items')->get() as $row) {
            $tenureMonths = $row->amount > 0 ? (int) ceil($row->principal / $row->amount) : 1;

            DB::table('debt_items')->where('id', $row->id)->update([
                'amount_borrowed' => $row->principal,
                'total_repayment_amount' => $row->principal,
                'monthly_repayment_amount' => $row->amount,
                'tenure_months' => max($tenureMonths, 1),
                'payments_made' => 0,
            ]);
        }

        DB::statement('ALTER TABLE debt_items ALTER COLUMN amount_borrowed SET NOT NULL');
        DB::statement('ALTER TABLE debt_items ALTER COLUMN total_repayment_amount SET NOT NULL');
        DB::statement('ALTER TABLE debt_items ALTER COLUMN monthly_repayment_amount SET NOT NULL');
        DB::statement('ALTER TABLE debt_items ALTER COLUMN tenure_months SET NOT NULL');

        Schema::table('debt_items', function (Blueprint $table) {
            $table->dropColumn(['principal', 'balance', 'amount']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('debt_items', function (Blueprint $table) {
            $table->decimal('principal', 14, 2)->nullable()->after('category');
            $table->decimal('balance', 14, 2)->nullable()->after('principal');
            $table->decimal('amount', 14, 2)->nullable()->after('balance');
        });

        foreach (DB::table('debt_items')->get() as $row) {
            DB::table('debt_items')->where('id', $row->id)->update([
                'principal' => $row->amount_borrowed,
                'balance' => $row->total_repayment_amount - ($row->monthly_repayment_amount * $row->payments_made),
                'amount' => $row->monthly_repayment_amount,
            ]);
        }

        DB::statement('ALTER TABLE debt_items ALTER COLUMN principal SET NOT NULL');
        DB::statement('ALTER TABLE debt_items ALTER COLUMN balance SET NOT NULL');
        DB::statement('ALTER TABLE debt_items ALTER COLUMN amount SET NOT NULL');

        Schema::table('debt_items', function (Blueprint $table) {
            $table->dropColumn(['amount_borrowed', 'total_repayment_amount', 'monthly_repayment_amount', 'tenure_months', 'payments_made']);
        });
    }
};
