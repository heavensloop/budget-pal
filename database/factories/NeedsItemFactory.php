<?php

namespace Database\Factories;

use App\Enums\NeedsItemStatus;
use App\Models\Category;
use App\Models\NeedsItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NeedsItem>
 */
class NeedsItemFactory extends Factory
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
            'category_id' => Category::factory(),
            'schedule_id' => null,
            'name' => fake()->words(2, true),
            'amount' => fake()->randomFloat(2, 1000, 100000),
            'currency_code' => 'NGN',
            'status' => NeedsItemStatus::Pending,
            'date_due' => null,
            'notes' => null,
        ];
    }
}
