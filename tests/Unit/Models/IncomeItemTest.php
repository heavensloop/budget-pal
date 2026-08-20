<?php

namespace Tests\Unit\Models;

use App\Enums\MonthlyRecurrence;
use App\Models\IncomeItem;
use App\Models\Schedule;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncomeItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_income_items_cannot_share_the_same_schedule()
    {
        $schedule = Schedule::factory()->create();
        IncomeItem::factory()->create(['schedule_id' => $schedule->id]);

        $this->expectException(QueryException::class);

        IncomeItem::factory()->create(['schedule_id' => $schedule->id]);
    }

    public function test_monthly_income_item_returns_the_next_due_date()
    {
        $schedule = Schedule::factory()->create(['recurrence' => MonthlyRecurrence::Monthly, 'start_date' => '2026-01-15']);
        $item = IncomeItem::factory()->create(['schedule_id' => $schedule->id]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 3, 10));

        $this->assertSame('2026-03-15', $nextPaymentDate->toDateString());
    }

    public function test_one_time_income_item_returns_its_due_date()
    {
        $schedule = Schedule::factory()->create(['recurrence' => null, 'start_date' => '2026-09-15']);
        $item = IncomeItem::factory()->create(['schedule_id' => $schedule->id]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 3, 10));

        $this->assertSame('2026-09-15', $nextPaymentDate->toDateString());
    }

    public function test_item_with_no_schedule_at_all_is_not_scheduled()
    {
        $item = IncomeItem::factory()->create(['schedule_id' => null]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 3, 10));

        $this->assertNull($nextPaymentDate);
    }
}
