<?php

namespace Database\Factories;

use App\Enums\RecurrenceFrequency;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'is_active' => true,
            'recurrence' => RecurrenceFrequency::Monthly,
            'start_date' => null,
            'end_date' => null,
            'due_day' => fake()->numberBetween(1, 28),
            'reminder_days_before' => null,
        ];
    }
}
