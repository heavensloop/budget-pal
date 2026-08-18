<?php

namespace Tests\Unit\Actions\Budget;

use App\Actions\Budget\SyncBudgetItem;
use App\Enums\BudgetItemType;
use App\Enums\ItemStatus;
use App\Models\BudgetItem;
use App\Models\BudgetMonth;
use App\Models\NeedsItem;
use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncBudgetItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_budget_item_projection_for_a_one_time_item_with_no_due_date()
    {
        $item = NeedsItem::factory()->create(['name' => 'Groceries', 'amount' => 15000]);

        $budgetItem = app(SyncBudgetItem::class)($item);

        $this->assertSame(NeedsItem::class, $budgetItem->source_type);
        $this->assertSame($item->id, $budgetItem->source_id);
        $this->assertSame($item->budget_month_id, $budgetItem->budget_month_id);
        $this->assertSame(BudgetItemType::Needs, $budgetItem->type);
        $this->assertSame('Groceries', $budgetItem->name);
        $this->assertSame('15000.00', $budgetItem->amount);
        $this->assertNull($budgetItem->date_due);
        $this->assertNull($budgetItem->message);
    }

    public function test_uses_the_items_own_date_due_for_a_one_time_item()
    {
        $item = NeedsItem::factory()->create(['date_due' => '2026-09-15']);

        $budgetItem = app(SyncBudgetItem::class)($item);

        $this->assertSame('2026-09-15', $budgetItem->date_due->toDateString());
        $this->assertSame('Due Sep 15, 2026', $budgetItem->message);
    }

    public function test_derives_the_due_date_from_the_schedules_due_day_for_a_recurring_item()
    {
        $budgetMonth = BudgetMonth::factory()->create(['year' => 2026, 'month' => 3]);
        $schedule = Schedule::factory()->create(['due_day' => 5]);
        $item = NeedsItem::factory()->create([
            'budget_month_id' => $budgetMonth->id,
            'schedule_id' => $schedule->id,
            'date_due' => null,
        ]);

        $budgetItem = app(SyncBudgetItem::class)($item);

        $this->assertSame('2026-03-05', $budgetItem->date_due->toDateString());
        $this->assertSame('Due on the 5th of the month', $budgetItem->message);
    }

    public function test_clamps_the_due_day_to_the_months_actual_length()
    {
        $budgetMonth = BudgetMonth::factory()->create(['year' => 2026, 'month' => 2]);
        $schedule = Schedule::factory()->create(['due_day' => 31]);
        $item = NeedsItem::factory()->create([
            'budget_month_id' => $budgetMonth->id,
            'schedule_id' => $schedule->id,
        ]);

        $budgetItem = app(SyncBudgetItem::class)($item);

        $this->assertSame('2026-02-28', $budgetItem->date_due->toDateString());
    }

    public function test_updates_the_existing_row_instead_of_creating_a_duplicate()
    {
        $item = NeedsItem::factory()->create(['status' => ItemStatus::Pending]);

        app(SyncBudgetItem::class)($item);

        $item->update(['status' => ItemStatus::Done]);
        app(SyncBudgetItem::class)($item->fresh());

        $this->assertSame(1, BudgetItem::count());
        $this->assertSame(ItemStatus::Done, BudgetItem::first()->status);
    }
}
