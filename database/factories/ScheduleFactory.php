<?php

namespace Database\Factories;

use App\Enums\MonthlyRecurrence;
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
            'recurrence' => MonthlyRecurrence::Monthly,
            'start_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'end_date' => null,
            'reminder_days_before' => null,
            'interval_months' => null,
            'months' => null,
        ];
    }

    /**
     * @return Factory<Schedule>
     */
    public function everyNMonths(int $intervalMonths): Factory
    {
        return $this->state(fn () => [
            'recurrence' => MonthlyRecurrence::EveryNMonths,
            'interval_months' => $intervalMonths,
        ]);
    }

    /**
     * @param  list<int>  $months  1 (January) through 12 (December)
     * @return Factory<Schedule>
     */
    public function specificMonths(array $months): Factory
    {
        return $this->state(fn () => [
            'recurrence' => MonthlyRecurrence::SpecificMonths,
            'months' => $months,
        ]);
    }
}
