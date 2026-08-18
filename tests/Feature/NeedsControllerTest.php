<?php

namespace Tests\Feature;

use App\Actions\Budget\SyncBudgetItem;
use App\Enums\ItemStatus;
use App\Models\BudgetItem;
use App\Models\BudgetMonth;
use App\Models\Category;
use App\Models\NeedsItem;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NeedsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('needs.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_index_only_returns_the_current_users_items()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create();

        $myMonth = BudgetMonth::factory()->create(['user_id' => $user->id, 'year' => 2026, 'month' => 3]);
        $theirMonth = BudgetMonth::factory()->create(['user_id' => $otherUser->id, 'year' => 2026, 'month' => 3]);

        NeedsItem::factory()->create([
            'budget_month_id' => $myMonth->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'My rent',
        ]);
        NeedsItem::factory()->create([
            'budget_month_id' => $theirMonth->id,
            'user_id' => $otherUser->id,
            'category_id' => $category->id,
            'name' => 'Their rent',
        ]);

        $response = $this->actingAs($user)->get(route('needs.index', ['year' => 2026, 'month' => 3]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Needs/Index')
            ->has('items', 1)
            ->where('items.0.name', 'My rent'),
        );
    }

    public function test_store_creates_a_one_time_item()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user)->post(route('needs.store', ['year' => 2026, 'month' => 3]), [
            'category_id' => $category->id,
            'name' => 'Birthday gift',
            'amount' => 25000,
            'date_due' => '2026-09-15',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('needs_items', [
            'user_id' => $user->id,
            'name' => 'Birthday gift',
            'amount' => '25000.00',
            'schedule_id' => null,
            'date_due' => '2026-09-15',
        ]);
        $this->assertSame(1, BudgetItem::count());
    }

    public function test_store_creates_a_recurring_item_with_a_schedule()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($user)->post(route('needs.store', ['year' => 2026, 'month' => 3]), [
            'category_id' => $category->id,
            'name' => 'Rent',
            'amount' => 60000,
            'is_recurring' => true,
            'due_day' => 1,
        ]);

        $item = NeedsItem::where('name', 'Rent')->firstOrFail();

        $this->assertNotNull($item->schedule_id);
        $this->assertTrue($item->schedule->is_active);
        $this->assertSame(1, $item->schedule->due_day);
        $this->assertNull($item->date_due);
    }

    public function test_store_validates_amount_is_numeric_and_non_negative()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user)->post(route('needs.store'), [
            'category_id' => $category->id,
            'name' => 'Bad amount',
            'amount' => -100,
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseMissing('needs_items', ['name' => 'Bad amount']);
    }

    public function test_update_only_changes_the_current_months_row()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $schedule = Schedule::factory()->create(['is_active' => true]);

        $februaryMonth = BudgetMonth::factory()->create(['user_id' => $user->id, 'year' => 2026, 'month' => 2]);
        $marchMonth = BudgetMonth::factory()->create(['user_id' => $user->id, 'year' => 2026, 'month' => 3]);

        $februaryItem = NeedsItem::factory()->create([
            'budget_month_id' => $februaryMonth->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'schedule_id' => $schedule->id,
            'amount' => 50000,
        ]);
        $marchItem = NeedsItem::factory()->create([
            'budget_month_id' => $marchMonth->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'schedule_id' => $schedule->id,
            'amount' => 50000,
        ]);

        $this->actingAs($user)->put(route('needs.update', $marchItem), [
            'category_id' => $category->id,
            'name' => $marchItem->name,
            'amount' => 75000,
            'is_recurring' => true,
            'due_day' => 1,
        ]);

        $this->assertSame('75000.00', $marchItem->fresh()->amount);
        $this->assertSame('50000.00', $februaryItem->fresh()->amount);
    }

    public function test_update_turning_off_recurring_deactivates_the_schedule()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $schedule = Schedule::factory()->create(['is_active' => true]);
        $item = NeedsItem::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'schedule_id' => $schedule->id,
        ]);

        $this->actingAs($user)->put(route('needs.update', $item), [
            'category_id' => $category->id,
            'name' => $item->name,
            'amount' => $item->amount,
        ]);

        $this->assertFalse($schedule->fresh()->is_active);
    }

    public function test_a_user_cannot_update_another_users_item()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create();
        $item = NeedsItem::factory()->create(['user_id' => $otherUser->id, 'category_id' => $category->id]);

        $response = $this->actingAs($user)->put(route('needs.update', $item), [
            'category_id' => $category->id,
            'name' => 'Hijacked',
            'amount' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_destroy_removes_the_item_and_its_budget_item_projection()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $item = NeedsItem::factory()->create(['user_id' => $user->id, 'category_id' => $category->id]);

        app(SyncBudgetItem::class)($item);
        $this->assertSame(1, BudgetItem::count());

        $response = $this->actingAs($user)->delete(route('needs.destroy', $item));

        $response->assertRedirect();
        $this->assertDatabaseMissing('needs_items', ['id' => $item->id]);
        $this->assertSame(0, BudgetItem::count());
    }

    public function test_a_user_cannot_destroy_another_users_item()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create();
        $item = NeedsItem::factory()->create(['user_id' => $otherUser->id, 'category_id' => $category->id]);

        $response = $this->actingAs($user)->delete(route('needs.destroy', $item));

        $response->assertForbidden();
        $this->assertDatabaseHas('needs_items', ['id' => $item->id]);
    }

    public function test_update_status_changes_the_items_status()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $item = NeedsItem::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => ItemStatus::Pending,
        ]);

        $response = $this->actingAs($user)->patch(route('needs.status', $item), ['status' => 'done']);

        $response->assertRedirect();
        $this->assertSame(ItemStatus::Done, $item->fresh()->status);
    }

    public function test_index_sorts_by_name()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $budgetMonth = BudgetMonth::factory()->create(['user_id' => $user->id, 'year' => 2026, 'month' => 3]);
        NeedsItem::factory()->create([
            'budget_month_id' => $budgetMonth->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Zebra',
        ]);
        NeedsItem::factory()->create([
            'budget_month_id' => $budgetMonth->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Apple',
        ]);

        $response = $this->actingAs($user)->get(
            route('needs.index', ['year' => 2026, 'month' => 3, 'sort' => 'name', 'direction' => 'asc']),
        );

        $response->assertInertia(fn ($page) => $page
            ->where('items.0.name', 'Apple')
            ->where('items.1.name', 'Zebra')
            ->where('sort', 'name')
            ->where('direction', 'asc'),
        );
    }

    public function test_index_sorts_by_name_descending()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $budgetMonth = BudgetMonth::factory()->create(['user_id' => $user->id, 'year' => 2026, 'month' => 3]);
        NeedsItem::factory()->create([
            'budget_month_id' => $budgetMonth->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Zebra',
        ]);
        NeedsItem::factory()->create([
            'budget_month_id' => $budgetMonth->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Apple',
        ]);

        $response = $this->actingAs($user)->get(
            route('needs.index', ['year' => 2026, 'month' => 3, 'sort' => 'name', 'direction' => 'desc']),
        );

        $response->assertInertia(fn ($page) => $page
            ->where('items.0.name', 'Zebra')
            ->where('items.1.name', 'Apple'),
        );
    }

    public function test_index_sorts_by_amount()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $budgetMonth = BudgetMonth::factory()->create(['user_id' => $user->id, 'year' => 2026, 'month' => 3]);
        NeedsItem::factory()->create([
            'budget_month_id' => $budgetMonth->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 50000,
        ]);
        NeedsItem::factory()->create([
            'budget_month_id' => $budgetMonth->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 5000,
        ]);

        $response = $this->actingAs($user)->get(
            route('needs.index', ['year' => 2026, 'month' => 3, 'sort' => 'amount', 'direction' => 'asc']),
        );

        $response->assertInertia(fn ($page) => $page
            ->where('items.0.amount', 5000)
            ->where('items.1.amount', 50000),
        );
    }

    public function test_index_sorts_by_category_name()
    {
        $user = User::factory()->create();
        $zCategory = Category::factory()->create(['name' => 'Zzz Category']);
        $aCategory = Category::factory()->create(['name' => 'Aaa Category']);
        $budgetMonth = BudgetMonth::factory()->create(['user_id' => $user->id, 'year' => 2026, 'month' => 3]);
        NeedsItem::factory()->create([
            'budget_month_id' => $budgetMonth->id,
            'user_id' => $user->id,
            'category_id' => $zCategory->id,
            'name' => 'First',
        ]);
        NeedsItem::factory()->create([
            'budget_month_id' => $budgetMonth->id,
            'user_id' => $user->id,
            'category_id' => $aCategory->id,
            'name' => 'Second',
        ]);

        $response = $this->actingAs($user)->get(
            route('needs.index', ['year' => 2026, 'month' => 3, 'sort' => 'category', 'direction' => 'asc']),
        );

        $response->assertInertia(fn ($page) => $page
            ->where('items.0.category', 'Aaa Category')
            ->where('items.1.category', 'Zzz Category'),
        );
    }

    public function test_index_sorts_by_status()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $budgetMonth = BudgetMonth::factory()->create(['user_id' => $user->id, 'year' => 2026, 'month' => 3]);
        NeedsItem::factory()->create([
            'budget_month_id' => $budgetMonth->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Pending item',
            'status' => ItemStatus::Pending,
        ]);
        NeedsItem::factory()->create([
            'budget_month_id' => $budgetMonth->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Done item',
            'status' => ItemStatus::Done,
        ]);

        $response = $this->actingAs($user)->get(
            route('needs.index', ['year' => 2026, 'month' => 3, 'sort' => 'status', 'direction' => 'asc']),
        );

        $response->assertInertia(fn ($page) => $page
            ->where('items.0.status', 'done')
            ->where('items.1.status', 'pending'),
        );
    }

    public function test_index_falls_back_to_name_for_an_invalid_sort_column()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $budgetMonth = BudgetMonth::factory()->create(['user_id' => $user->id, 'year' => 2026, 'month' => 3]);
        NeedsItem::factory()->create([
            'budget_month_id' => $budgetMonth->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user)->get(
            route('needs.index', ['year' => 2026, 'month' => 3, 'sort' => 'notes']),
        );

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('sort', 'name'));
    }

    public function test_index_defaults_to_name_ascending_when_no_sort_is_given()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $budgetMonth = BudgetMonth::factory()->create(['user_id' => $user->id, 'year' => 2026, 'month' => 3]);
        NeedsItem::factory()->create([
            'budget_month_id' => $budgetMonth->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Zebra',
        ]);
        NeedsItem::factory()->create([
            'budget_month_id' => $budgetMonth->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Apple',
        ]);

        $response = $this->actingAs($user)->get(route('needs.index', ['year' => 2026, 'month' => 3]));

        $response->assertInertia(fn ($page) => $page
            ->where('items.0.name', 'Apple')
            ->where('items.1.name', 'Zebra')
            ->where('sort', 'name')
            ->where('direction', 'asc'),
        );
    }
}
