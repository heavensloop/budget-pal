<?php

namespace Database\Factories;

use App\Models\BudgetMonth;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetMonth>
 */
class BudgetMonthFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'year' => fake()->numberBetween(2024, 2030),
            'month' => fake()->numberBetween(1, 12),
            'wants_budget_cap' => fake()->randomFloat(2, 0, 500000),
        ];
    }
}
