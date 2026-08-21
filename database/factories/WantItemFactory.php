<?php

namespace Database\Factories;

use App\Enums\WantCategory;
use App\Enums\WantItemStatus;
use App\Models\User;
use App\Models\WantItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WantItem>
 */
class WantItemFactory extends Factory
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
            'name' => fake()->words(2, true),
            'category' => fake()->randomElement(WantCategory::cases()),
            'amount' => fake()->randomFloat(2, 5000, 500000),
            'currency_code' => 'NGN',
            'status' => WantItemStatus::PLANNED,
            'position' => fake()->unique()->numberBetween(1, 100000),
            'purchased_at' => null,
            'notes' => null,
        ];
    }
}
