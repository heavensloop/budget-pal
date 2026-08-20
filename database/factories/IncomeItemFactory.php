<?php

namespace Database\Factories;

use App\Enums\IncomeCategory;
use App\Enums\IncomeItemStatus;
use App\Models\IncomeItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncomeItem>
 */
class IncomeItemFactory extends Factory
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
            'category' => fake()->randomElement(IncomeCategory::cases()),
            'schedule_id' => null,
            'name' => fake()->words(2, true),
            'amount' => fake()->randomFloat(2, 1000, 500000),
            'currency_code' => 'NGN',
            'status' => IncomeItemStatus::Pending,
            'notes' => null,
        ];
    }
}
