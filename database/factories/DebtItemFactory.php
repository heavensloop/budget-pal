<?php

namespace Database\Factories;

use App\Enums\DebtItemStatus;
use App\Enums\LoanCategory;
use App\Models\DebtItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DebtItem>
 */
class DebtItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amountBorrowed = fake()->randomFloat(2, 50000, 2000000);
        $tenureMonths = fake()->numberBetween(6, 24);

        return [
            'user_id' => User::factory(),
            'category' => fake()->randomElement(LoanCategory::cases()),
            'schedule_id' => null,
            'name' => fake()->words(2, true),
            'amount_borrowed' => $amountBorrowed,
            'total_repayment_amount' => $amountBorrowed,
            'monthly_repayment_amount' => round($amountBorrowed / $tenureMonths, 2),
            'tenure_months' => $tenureMonths,
            'payments_made' => 0,
            'currency_code' => 'NGN',
            'status' => DebtItemStatus::Pending,
            'last_payment_date' => null,
            'notes' => null,
        ];
    }
}
