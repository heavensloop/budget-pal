<?php

namespace Database\Factories;

use App\Enums\SavingsItemStatus;
use App\Enums\SavingsItemType;
use App\Models\SavingsItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavingsItem>
 */
class SavingsItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $targetAmount = fake()->randomFloat(2, 100000, 5000000);
        $installmentAmount = round($targetAmount / fake()->numberBetween(6, 24), 2);

        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(SavingsItemType::cases()),
            'schedule_id' => null,
            'name' => fake()->words(2, true),
            'target_amount' => $targetAmount,
            'installment_amount' => $installmentAmount,
            'installments_made' => 0,
            'target_profit' => fake()->boolean() ? fake()->randomFloat(2, 5000, 500000) : null,
            'maturity_date' => fake()->boolean()
                ? fake()->dateTimeBetween('+6 months', '+3 years')->format('Y-m-d')
                : null,
            'currency_code' => 'NGN',
            'status' => SavingsItemStatus::PENDING,
            'last_contribution_date' => null,
            'notes' => null,
        ];
    }
}
