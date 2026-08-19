<?php

namespace Tests\Unit\Models;

use App\Enums\RecurrenceFrequency;
use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_frontend_array_includes_the_reminder_days_before()
    {
        $schedule = Schedule::factory()->create([
            'recurrence' => RecurrenceFrequency::Monthly,
            'start_date' => '2026-01-15',
            'end_date' => '2026-12-31',
            'reminder_days_before' => 3,
        ]);

        $this->assertSame([
            'recurrence' => 'monthly',
            'startDate' => '2026-01-15',
            'endDate' => '2026-12-31',
            'reminderDaysBefore' => 3,
        ], $schedule->toFrontendArray());
    }

    public function test_to_frontend_array_defaults_reminder_days_before_to_null()
    {
        $schedule = Schedule::factory()->create(['reminder_days_before' => null]);

        $this->assertNull($schedule->toFrontendArray()['reminderDaysBefore']);
    }
}
