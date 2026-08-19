<?php

namespace Tests\Unit\Models;

use App\Enums\RecurrenceFrequency;
use App\Models\NeedsItem;
use App\Models\Schedule;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NeedsItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_needs_items_cannot_share_the_same_schedule()
    {
        $schedule = Schedule::factory()->create();
        NeedsItem::factory()->create(['schedule_id' => $schedule->id]);

        $this->expectException(QueryException::class);

        NeedsItem::factory()->create(['schedule_id' => $schedule->id]);
    }

    // Monthly

    public function test_monthly_item_returns_this_months_due_date_when_not_yet_passed()
    {
        $schedule = Schedule::factory()->create(['recurrence' => RecurrenceFrequency::Monthly, 'start_date' => '2026-01-15']);
        $item = NeedsItem::factory()->create(['schedule_id' => $schedule->id]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 3, 10));

        $this->assertSame('2026-03-15', $nextPaymentDate->toDateString());
    }

    public function test_monthly_item_returns_todays_due_date_when_due_today()
    {
        $schedule = Schedule::factory()->create(['recurrence' => RecurrenceFrequency::Monthly, 'start_date' => '2026-01-15']);
        $item = NeedsItem::factory()->create(['schedule_id' => $schedule->id]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 3, 15));

        $this->assertSame('2026-03-15', $nextPaymentDate->toDateString());
    }

    public function test_monthly_item_rolls_forward_to_next_month_once_this_months_due_date_has_passed()
    {
        $schedule = Schedule::factory()->create(['recurrence' => RecurrenceFrequency::Monthly, 'start_date' => '2026-01-15']);
        $item = NeedsItem::factory()->create(['schedule_id' => $schedule->id]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 3, 20));

        $this->assertSame('2026-04-15', $nextPaymentDate->toDateString());
    }

    public function test_monthly_item_clamps_this_months_due_date_to_the_months_length()
    {
        $schedule = Schedule::factory()->create(['recurrence' => RecurrenceFrequency::Monthly, 'start_date' => '2026-01-31']);
        $item = NeedsItem::factory()->create(['schedule_id' => $schedule->id]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 2, 10));

        $this->assertSame('2026-02-28', $nextPaymentDate->toDateString());
    }

    public function test_monthly_item_clamps_next_months_due_date_to_the_months_length()
    {
        $schedule = Schedule::factory()->create(['recurrence' => RecurrenceFrequency::Monthly, 'start_date' => '2026-01-30']);
        $item = NeedsItem::factory()->create(['schedule_id' => $schedule->id]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 1, 31));

        $this->assertSame('2026-02-28', $nextPaymentDate->toDateString());
    }

    // Yearly

    public function test_yearly_item_returns_this_years_date_when_not_yet_passed()
    {
        $schedule = Schedule::factory()->create(['recurrence' => RecurrenceFrequency::Yearly, 'start_date' => '2024-03-15']);
        $item = NeedsItem::factory()->create(['schedule_id' => $schedule->id]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 2, 1));

        $this->assertSame('2026-03-15', $nextPaymentDate->toDateString());
    }

    public function test_yearly_item_rolls_forward_to_next_year_once_this_years_date_has_passed()
    {
        $schedule = Schedule::factory()->create(['recurrence' => RecurrenceFrequency::Yearly, 'start_date' => '2026-03-15']);
        $item = NeedsItem::factory()->create(['schedule_id' => $schedule->id]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 4, 1));

        $this->assertSame('2027-03-15', $nextPaymentDate->toDateString());
    }

    public function test_yearly_item_clamps_a_leap_day_anchor_to_february_28_on_a_non_leap_year()
    {
        $schedule = Schedule::factory()->create(['recurrence' => RecurrenceFrequency::Yearly, 'start_date' => '2024-02-29']);
        $item = NeedsItem::factory()->create(['schedule_id' => $schedule->id]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 2, 10));

        $this->assertSame('2026-02-28', $nextPaymentDate->toDateString());
    }

    // Weekly

    public function test_weekly_item_returns_the_anchor_date_when_due_today()
    {
        $schedule = Schedule::factory()->create(['recurrence' => RecurrenceFrequency::Weekly, 'start_date' => '2026-03-02']);
        $item = NeedsItem::factory()->create(['schedule_id' => $schedule->id]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 3, 2));

        $this->assertSame('2026-03-02', $nextPaymentDate->toDateString());
    }

    public function test_weekly_item_rolls_forward_to_the_next_occurrence()
    {
        $schedule = Schedule::factory()->create(['recurrence' => RecurrenceFrequency::Weekly, 'start_date' => '2026-03-02']);
        $item = NeedsItem::factory()->create(['schedule_id' => $schedule->id]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 3, 5));

        $this->assertSame('2026-03-09', $nextPaymentDate->toDateString());
    }

    // Biweekly

    public function test_biweekly_item_rolls_forward_to_the_next_occurrence()
    {
        $schedule = Schedule::factory()->create(['recurrence' => RecurrenceFrequency::Biweekly, 'start_date' => '2026-01-05']);
        $item = NeedsItem::factory()->create(['schedule_id' => $schedule->id]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 1, 10));

        $this->assertSame('2026-01-19', $nextPaymentDate->toDateString());
    }

    // Bounds

    public function test_recurring_item_returns_null_once_past_its_end_date()
    {
        $schedule = Schedule::factory()->create([
            'recurrence' => RecurrenceFrequency::Monthly,
            'start_date' => '2026-01-15',
            'end_date' => '2026-02-01',
        ]);
        $item = NeedsItem::factory()->create(['schedule_id' => $schedule->id]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 3, 1));

        $this->assertNull($nextPaymentDate);
    }

    public function test_recurring_item_with_a_future_start_date_returns_the_start_date()
    {
        $schedule = Schedule::factory()->create(['recurrence' => RecurrenceFrequency::Monthly, 'start_date' => '2026-06-01']);
        $item = NeedsItem::factory()->create(['schedule_id' => $schedule->id]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 3, 1));

        $this->assertSame('2026-06-01', $nextPaymentDate->toDateString());
    }

    public function test_inactive_schedule_is_not_scheduled()
    {
        $schedule = Schedule::factory()->create(['is_active' => false, 'recurrence' => RecurrenceFrequency::Monthly, 'start_date' => '2026-01-15']);
        $item = NeedsItem::factory()->create(['schedule_id' => $schedule->id]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 3, 10));

        $this->assertNull($nextPaymentDate);
    }

    // One-time (recurrence null)

    public function test_one_time_item_returns_a_future_due_date()
    {
        $schedule = Schedule::factory()->create(['recurrence' => null, 'start_date' => '2026-09-15']);
        $item = NeedsItem::factory()->create(['schedule_id' => $schedule->id]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 3, 10));

        $this->assertSame('2026-09-15', $nextPaymentDate->toDateString());
    }

    public function test_one_time_item_returns_todays_due_date()
    {
        $schedule = Schedule::factory()->create(['recurrence' => null, 'start_date' => '2026-03-10']);
        $item = NeedsItem::factory()->create(['schedule_id' => $schedule->id]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 3, 10));

        $this->assertSame('2026-03-10', $nextPaymentDate->toDateString());
    }

    public function test_one_time_item_with_a_past_due_date_is_not_scheduled()
    {
        $schedule = Schedule::factory()->create(['recurrence' => null, 'start_date' => '2026-01-01']);
        $item = NeedsItem::factory()->create(['schedule_id' => $schedule->id]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 3, 10));

        $this->assertNull($nextPaymentDate);
    }

    public function test_item_with_no_schedule_at_all_is_not_scheduled()
    {
        $item = NeedsItem::factory()->create(['schedule_id' => null]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 3, 10));

        $this->assertNull($nextPaymentDate);
    }
}
