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
            'start_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'end_date' => null,
            'reminder_days_before' => null,
        ];
    }
}
