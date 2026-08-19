<?php

namespace Tests\Unit\Models;

use App\Enums\MonthlyRecurrence;
use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_frontend_array_includes_the_reminder_days_before()
    {
        $schedule = Schedule::factory()->create([
            'recurrence' => MonthlyRecurrence::Monthly,
            'start_date' => '2026-01-15',
            'end_date' => '2026-12-31',
            'reminder_days_before' => 3,
        ]);

        $this->assertSame([
            'recurrence' => 'monthly',
            'startDate' => '2026-01-15',
            'endDate' => '2026-12-31',
            'reminderDaysBefore' => 3,
            'intervalMonths' => null,
            'months' => null,
        ], $schedule->toFrontendArray());
    }

    public function test_to_frontend_array_defaults_reminder_days_before_to_null()
    {
        $schedule = Schedule::factory()->create(['reminder_days_before' => null]);

        $this->assertNull($schedule->toFrontendArray()['reminderDaysBefore']);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, list<int>|null}>
     */
    public static function getDifferentMonthRecurrenceMonthValuesData(): iterable
    {
        yield 'monthly has no months' => [
            ['recurrence' => MonthlyRecurrence::Monthly, 'months' => null],
            null,
        ];

        yield 'every n months has no months' => [
            ['recurrence' => MonthlyRecurrence::EveryNMonths, 'interval_months' => 2, 'months' => null],
            null,
        ];

        yield 'specific months carries the selected months' => [
            ['recurrence' => MonthlyRecurrence::SpecificMonths, 'months' => [1, 3, 5]],
            [1, 3, 5],
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>|null  $expectedMonths
     */
    #[DataProvider('getDifferentMonthRecurrenceMonthValuesData')]
    public function test_different_month_values_are_returned_correctly(array $attributes, ?array $expectedMonths)
    {
        $schedule = new Schedule($attributes);

        $this->assertSame($expectedMonths, $schedule->toFrontendArray()['months']);
    }
}
