<?php

namespace Tests\Unit\Models;

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

    public function test_recurring_item_returns_this_months_due_date_when_not_yet_passed()
    {
        $schedule = Schedule::factory()->create(['is_active' => true, 'due_day' => 15]);
        $item = NeedsItem::factory()->create(['schedule_id' => $schedule->id]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 3, 10));

        $this->assertSame('2026-03-15', $nextPaymentDate->toDateString());
    }

    public function test_recurring_item_returns_todays_due_date_when_due_today()
    {
        $schedule = Schedule::factory()->create(['is_active' => true, 'due_day' => 15]);
        $item = NeedsItem::factory()->create(['schedule_id' => $schedule->id]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 3, 15));

        $this->assertSame('2026-03-15', $nextPaymentDate->toDateString());
    }

    public function test_recurring_item_rolls_forward_to_next_month_once_this_months_due_date_has_passed()
    {
        $schedule = Schedule::factory()->create(['is_active' => true, 'due_day' => 15]);
        $item = NeedsItem::factory()->create(['schedule_id' => $schedule->id]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 3, 20));

        $this->assertSame('2026-04-15', $nextPaymentDate->toDateString());
    }

    public function test_recurring_item_clamps_this_months_due_date_to_the_months_length()
    {
        $schedule = Schedule::factory()->create(['is_active' => true, 'due_day' => 31]);
        $item = NeedsItem::factory()->create(['schedule_id' => $schedule->id]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 2, 10));

        $this->assertSame('2026-02-28', $nextPaymentDate->toDateString());
    }

    public function test_recurring_item_clamps_next_months_due_date_to_the_months_length()
    {
        $schedule = Schedule::factory()->create(['is_active' => true, 'due_day' => 30]);
        $item = NeedsItem::factory()->create(['schedule_id' => $schedule->id]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 1, 31));

        $this->assertSame('2026-02-28', $nextPaymentDate->toDateString());
    }

    public function test_inactive_schedule_is_treated_like_a_one_time_item()
    {
        $schedule = Schedule::factory()->create(['is_active' => false, 'due_day' => 15]);
        $item = NeedsItem::factory()->create(['schedule_id' => $schedule->id, 'date_due' => null]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 3, 10));

        $this->assertNull($nextPaymentDate);
    }

    public function test_one_time_item_returns_a_future_due_date()
    {
        $item = NeedsItem::factory()->create(['schedule_id' => null, 'date_due' => '2026-09-15']);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 3, 10));

        $this->assertSame('2026-09-15', $nextPaymentDate->toDateString());
    }

    public function test_one_time_item_returns_todays_due_date()
    {
        $item = NeedsItem::factory()->create(['schedule_id' => null, 'date_due' => '2026-03-10']);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 3, 10));

        $this->assertSame('2026-03-10', $nextPaymentDate->toDateString());
    }

    public function test_one_time_item_with_a_past_due_date_is_not_scheduled()
    {
        $item = NeedsItem::factory()->create(['schedule_id' => null, 'date_due' => '2026-01-01']);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 3, 10));

        $this->assertNull($nextPaymentDate);
    }

    public function test_item_with_no_schedule_and_no_due_date_is_not_scheduled()
    {
        $item = NeedsItem::factory()->create(['schedule_id' => null, 'date_due' => null]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 3, 10));

        $this->assertNull($nextPaymentDate);
    }
}
