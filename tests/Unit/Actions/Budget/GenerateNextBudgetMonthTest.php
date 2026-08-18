<?php

namespace Tests\Unit\Actions\Budget;

use App\Actions\Budget\GenerateNextBudgetMonth;
use App\Enums\ItemStatus;
use App\Models\BudgetItem;
use App\Models\BudgetMonth;
use App\Models\Category;
use App\Models\NeedsItem;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateNextBudgetMonthTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_the_target_month_when_no_previous_month_exists()
    {
        $user = User::factory()->create();

        $budgetMonth = app(GenerateNextBudgetMonth::class)($user, 2026, 3);

        $this->assertSame(2026, $budgetMonth->year);
        $this->assertSame(3, $budgetMonth->month);
        $this->assertSame(0, $budgetMonth->needsItems()->count());
    }

    public function test_does_not_carry_forward_a_non_recurring_item()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $februaryMonth = BudgetMonth::factory()->create(['user_id' => $user->id, 'year' => 2026, 'month' => 2]);

        NeedsItem::factory()->create([
            'budget_month_id' => $februaryMonth->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'schedule_id' => null,
            'name' => 'One-time gift',
        ]);

        $marchMonth = app(GenerateNextBudgetMonth::class)($user, 2026, 3);

        $this->assertSame(0, $marchMonth->needsItems()->count());
    }

    public function test_carries_forward_an_active_recurring_item_with_the_prior_months_amount()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $schedule = Schedule::factory()->create(['is_active' => true, 'due_day' => 1]);
        $februaryMonth = BudgetMonth::factory()->create(['user_id' => $user->id, 'year' => 2026, 'month' => 2]);

        $rent = NeedsItem::factory()->create([
            'budget_month_id' => $februaryMonth->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'schedule_id' => $schedule->id,
            'name' => 'Rent',
            'amount' => 60000,
            'status' => ItemStatus::Done,
        ]);

        $marchMonth = app(GenerateNextBudgetMonth::class)($user, 2026, 3);

        $this->assertSame(1, $marchMonth->needsItems()->count());

        $carried = $marchMonth->needsItems()->first();
        $this->assertSame('Rent', $carried->name);
        $this->assertSame('60000.00', $carried->amount);
        $this->assertSame($schedule->id, $carried->schedule_id);
        $this->assertSame($category->id, $carried->category_id);
        $this->assertSame(ItemStatus::Pending, $carried->status);

        $this->assertDatabaseHas('budget_items', [
            'source_type' => NeedsItem::class,
            'source_id' => $carried->id,
            'name' => 'Rent',
        ]);
    }

    public function test_amount_edited_in_the_prior_month_is_what_gets_carried_forward()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $schedule = Schedule::factory()->create(['is_active' => true]);
        $februaryMonth = BudgetMonth::factory()->create(['user_id' => $user->id, 'year' => 2026, 'month' => 2]);

        NeedsItem::factory()->create([
            'budget_month_id' => $februaryMonth->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'schedule_id' => $schedule->id,
            'amount' => 75000,
        ]);

        $marchMonth = app(GenerateNextBudgetMonth::class)($user, 2026, 3);

        $this->assertSame('75000.00', $marchMonth->needsItems()->first()->amount);
    }

    public function test_does_not_carry_forward_an_item_whose_schedule_is_inactive()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $schedule = Schedule::factory()->create(['is_active' => false]);
        $februaryMonth = BudgetMonth::factory()->create(['user_id' => $user->id, 'year' => 2026, 'month' => 2]);

        NeedsItem::factory()->create([
            'budget_month_id' => $februaryMonth->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'schedule_id' => $schedule->id,
        ]);

        $marchMonth = app(GenerateNextBudgetMonth::class)($user, 2026, 3);

        $this->assertSame(0, $marchMonth->needsItems()->count());
    }

    public function test_is_idempotent_when_run_twice_for_the_same_month()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $schedule = Schedule::factory()->create(['is_active' => true]);
        $februaryMonth = BudgetMonth::factory()->create(['user_id' => $user->id, 'year' => 2026, 'month' => 2]);

        NeedsItem::factory()->create([
            'budget_month_id' => $februaryMonth->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'schedule_id' => $schedule->id,
        ]);

        app(GenerateNextBudgetMonth::class)($user, 2026, 3);
        $marchMonth = app(GenerateNextBudgetMonth::class)($user, 2026, 3);

        $this->assertSame(1, $marchMonth->needsItems()->count());
        $this->assertSame(1, BudgetItem::count());
    }

    public function test_handles_the_december_to_january_year_rollover()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $schedule = Schedule::factory()->create(['is_active' => true]);
        $decemberMonth = BudgetMonth::factory()->create(['user_id' => $user->id, 'year' => 2026, 'month' => 12]);

        NeedsItem::factory()->create([
            'budget_month_id' => $decemberMonth->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'schedule_id' => $schedule->id,
        ]);

        $januaryMonth = app(GenerateNextBudgetMonth::class)($user, 2027, 1);

        $this->assertSame(1, $januaryMonth->needsItems()->count());
    }
}
