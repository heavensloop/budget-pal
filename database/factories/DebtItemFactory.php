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
        $principal = fake()->randomFloat(2, 50000, 2000000);

        return [
            'user_id' => User::factory(),
            'category' => fake()->randomElement(LoanCategory::cases()),
            'schedule_id' => null,
            'name' => fake()->words(2, true),
            'principal' => $principal,
            'balance' => $principal,
            'amount' => fake()->randomFloat(2, 1000, 50000),
            'currency_code' => 'NGN',
            'status' => DebtItemStatus::Pending,
            'last_payment_date' => null,
            'notes' => null,
        ];
    }
}
