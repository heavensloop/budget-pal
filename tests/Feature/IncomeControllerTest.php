<?php

namespace Tests\Feature;

use App\Enums\IncomeCategory;
use App\Enums\IncomeItemStatus;
use App\Enums\MonthlyRecurrence;
use App\Models\IncomeItem;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('income.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_index_only_returns_the_current_users_items()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        IncomeItem::factory()->create([
            'user_id' => $user->id,
            'name' => 'My salary',
        ]);
        IncomeItem::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'Their salary',
        ]);

        $response = $this->actingAs($user)->get(route('income.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Income/Index')
            ->has('items', 1)
            ->where('items.0.name', 'My salary'),
        );
    }

    public function test_store_creates_an_income_item_with_a_recurring_schedule()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('income.store'), [
            'category' => 'salary',
            'name' => 'Day job',
            'amount' => 350000,
            'schedule' => ['recurrence' => 'monthly', 'start_date' => '2026-03-01'],
        ]);

        $item = IncomeItem::where('name', 'Day job')->firstOrFail();

        $this->assertSame(IncomeCategory::Salary, $item->category);
        $this->assertSame('350000.00', $item->amount);
        $this->assertNotNull($item->schedule_id);
        $this->assertSame(MonthlyRecurrence::Monthly, $item->schedule->recurrence);
    }

    public function test_store_validates_amount_is_numeric_and_non_negative()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('income.store'), [
            'category' => 'salary',
            'name' => 'Bad amount',
            'amount' => -100,
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseMissing('income_items', ['name' => 'Bad amount']);
    }

    public function test_store_validates_the_category_is_a_known_value()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('income.store'), [
            'category' => 'not-a-real-category',
            'name' => 'Bad category',
            'amount' => 1000,
        ]);

        $response->assertSessionHasErrors('category');
    }

    public function test_store_rejects_a_duplicate_one_time_item_name()
    {
        $user = User::factory()->create();
        IncomeItem::factory()->create([
            'user_id' => $user->id,
            'schedule_id' => null,
            'name' => 'Birthday gift',
        ]);

        $response = $this->actingAs($user)->post(route('income.store'), [
            'category' => 'gift',
            'name' => 'Birthday gift',
            'amount' => 1000,
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame(1, IncomeItem::where('name', 'Birthday gift')->count());
    }

    public function test_update_changes_the_items_fields()
    {
        $user = User::factory()->create();
        $item = IncomeItem::factory()->create([
            'user_id' => $user->id,
            'category' => IncomeCategory::Salary,
            'amount' => 300000,
        ]);

        $this->actingAs($user)->put(route('income.update', $item), [
            'category' => 'freelance',
            'name' => $item->name,
            'amount' => 450000,
        ]);

        $item->refresh();

        $this->assertSame('450000.00', $item->amount);
        $this->assertSame(IncomeCategory::Freelance, $item->category);
    }

    public function test_update_without_a_schedule_removes_the_items_existing_schedule()
    {
        $user = User::factory()->create();
        $schedule = Schedule::factory()->create(['is_active' => true]);
        $item = IncomeItem::factory()->create([
            'user_id' => $user->id,
            'schedule_id' => $schedule->id,
        ]);

        $this->actingAs($user)->put(route('income.update', $item), [
            'category' => $item->category->value,
            'name' => $item->name,
            'amount' => $item->amount,
        ]);

        $this->assertNull($item->fresh()->schedule_id);
        $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
    }

    public function test_a_user_cannot_update_another_users_item()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $item = IncomeItem::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->put(route('income.update', $item), [
            'category' => 'salary',
            'name' => 'Hijacked',
            'amount' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_destroy_removes_the_item()
    {
        $user = User::factory()->create();
        $item = IncomeItem::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('income.destroy', $item));

        $response->assertRedirect();
        $this->assertDatabaseMissing('income_items', ['id' => $item->id]);
    }

    public function test_a_user_cannot_destroy_another_users_item()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $item = IncomeItem::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->delete(route('income.destroy', $item));

        $response->assertForbidden();
        $this->assertDatabaseHas('income_items', ['id' => $item->id]);
    }

    public function test_archiving_an_item_hides_it_from_the_default_index()
    {
        $user = User::factory()->create();
        $item = IncomeItem::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->patch(route('income.status', $item), ['status' => 'archived']);

        $this->assertSame(IncomeItemStatus::Archived, $item->fresh()->status);

        $response = $this->actingAs($user)->get(route('income.index'));

        $response->assertInertia(fn ($page) => $page->has('items', 0));
    }

    public function test_show_archived_includes_archived_items_in_the_same_list()
    {
        $user = User::factory()->create();

        IncomeItem::factory()->create([
            'user_id' => $user->id,
            'name' => 'Active income',
            'status' => IncomeItemStatus::Pending,
        ]);
        IncomeItem::factory()->create([
            'user_id' => $user->id,
            'name' => 'Archived income',
            'status' => IncomeItemStatus::Archived,
        ]);

        $response = $this->actingAs($user)->get(route('income.index', ['show_archived' => 1]));

        $response->assertInertia(fn ($page) => $page
            ->has('items', 2)
            ->where('items.0.name', 'Active income')
            ->where('items.1.name', 'Archived income')
            ->where('showArchived', true),
        );
    }

    public function test_restoring_an_item_sets_it_back_to_pending()
    {
        $user = User::factory()->create();
        $item = IncomeItem::factory()->create([
            'user_id' => $user->id,
            'status' => IncomeItemStatus::Archived,
        ]);

        $response = $this->actingAs($user)->patch(route('income.status', $item), ['status' => 'pending']);

        $response->assertRedirect();
        $this->assertSame(IncomeItemStatus::Pending, $item->fresh()->status);
    }

    public function test_index_sorts_by_amount()
    {
        $user = User::factory()->create();
        IncomeItem::factory()->create([
            'user_id' => $user->id,
            'amount' => 50000,
        ]);
        IncomeItem::factory()->create([
            'user_id' => $user->id,
            'amount' => 5000,
        ]);

        $response = $this->actingAs($user)->get(
            route('income.index', ['sort' => 'amount', 'direction' => 'asc']),
        );

        $response->assertInertia(fn ($page) => $page
            ->where('items.0.amount', 5000)
            ->where('items.1.amount', 50000),
        );
    }
}
