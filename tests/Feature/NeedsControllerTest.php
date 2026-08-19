<?php

namespace Tests\Feature;

use App\Enums\NeedsItemStatus;
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

        NeedsItem::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'My rent',
        ]);
        NeedsItem::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $category->id,
            'name' => 'Their rent',
        ]);

        $response = $this->actingAs($user)->get(route('needs.index'));

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

        $response = $this->actingAs($user)->post(route('needs.store'), [
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
    }

    public function test_store_creates_a_recurring_item_with_a_schedule()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($user)->post(route('needs.store'), [
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

    public function test_store_rejects_a_duplicate_one_time_item_name()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        NeedsItem::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'schedule_id' => null,
            'name' => 'Birthday gift',
        ]);

        $response = $this->actingAs($user)->post(route('needs.store'), [
            'category_id' => $category->id,
            'name' => 'Birthday gift',
            'amount' => 1000,
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame(1, NeedsItem::where('name', 'Birthday gift')->count());
    }

    public function test_store_allows_a_one_time_item_with_the_same_name_as_another_users_item()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create();
        NeedsItem::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $category->id,
            'schedule_id' => null,
            'name' => 'Birthday gift',
        ]);

        $response = $this->actingAs($user)->post(route('needs.store'), [
            'category_id' => $category->id,
            'name' => 'Birthday gift',
            'amount' => 1000,
        ]);

        $response->assertSessionHasNoErrors();
    }

    public function test_store_allows_a_one_time_item_with_the_same_name_as_a_recurring_item()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $schedule = Schedule::factory()->create(['is_active' => true]);
        NeedsItem::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'schedule_id' => $schedule->id,
            'name' => 'Rent',
        ]);

        $response = $this->actingAs($user)->post(route('needs.store'), [
            'category_id' => $category->id,
            'name' => 'Rent',
            'amount' => 1000,
        ]);

        $response->assertSessionHasNoErrors();
    }

    public function test_update_allows_keeping_a_one_time_items_own_name()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $item = NeedsItem::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'schedule_id' => null,
            'name' => 'Birthday gift',
        ]);

        $response = $this->actingAs($user)->put(route('needs.update', $item), [
            'category_id' => $category->id,
            'name' => 'Birthday gift',
            'amount' => 2000,
        ]);

        $response->assertSessionHasNoErrors();
    }

    public function test_update_changes_the_items_fields()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $item = NeedsItem::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 50000,
        ]);

        $this->actingAs($user)->put(route('needs.update', $item), [
            'category_id' => $category->id,
            'name' => $item->name,
            'amount' => 75000,
            'is_recurring' => true,
            'due_day' => 1,
        ]);

        $this->assertSame('75000.00', $item->fresh()->amount);
    }

    public function test_update_sets_a_recurring_due_day_sent_as_a_string_by_the_form()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $schedule = Schedule::factory()->create(['is_active' => true, 'due_day' => null]);
        $item = NeedsItem::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'schedule_id' => $schedule->id,
            'date_due' => null,
        ]);

        // Real form submissions arrive as strings, unlike PHP-array test payloads.
        $response = $this->actingAs($user)->put(route('needs.update', $item), [
            'category_id' => (string) $category->id,
            'name' => $item->name,
            'amount' => (string) $item->amount,
            'is_recurring' => '1',
            'due_day' => '15',
        ]);

        $response->assertRedirect();
        $this->assertSame(15, $schedule->fresh()->due_day);
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

    public function test_destroy_removes_the_item()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $item = NeedsItem::factory()->create(['user_id' => $user->id, 'category_id' => $category->id]);

        $response = $this->actingAs($user)->delete(route('needs.destroy', $item));

        $response->assertRedirect();
        $this->assertDatabaseMissing('needs_items', ['id' => $item->id]);
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
            'status' => NeedsItemStatus::Pending,
        ]);

        $response = $this->actingAs($user)->patch(route('needs.status', $item), ['status' => 'done']);

        $response->assertRedirect();
        $this->assertSame(NeedsItemStatus::Done, $item->fresh()->status);
    }

    public function test_archiving_an_item_hides_it_from_the_default_index()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $item = NeedsItem::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Old gym membership',
        ]);

        $this->actingAs($user)->patch(route('needs.status', $item), ['status' => 'archived']);

        $this->assertSame(NeedsItemStatus::Archived, $item->fresh()->status);

        $response = $this->actingAs($user)->get(route('needs.index'));

        $response->assertInertia(fn ($page) => $page
            ->has('items', 0)
            ->where('archivedItems', []),
        );
    }

    public function test_show_archived_returns_archived_items_in_a_separate_list()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        NeedsItem::factory()->times(4)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => NeedsItemStatus::Pending,
        ]);
        NeedsItem::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Archived need',
            'status' => NeedsItemStatus::Archived,
        ]);

        $response = $this->actingAs($user)->get(route('needs.index', ['show_archived' => 1]));

        $response->assertInertia(fn ($page) => $page
            ->has('items', 4)
            ->has('archivedItems', 1)
            ->where('archivedItems.0.name', 'Archived need')
            ->where('showArchived', true),
        );
    }

    public function test_index_includes_the_next_payment_date_for_a_recurring_item()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $schedule = Schedule::factory()->create(['is_active' => true, 'due_day' => 15]);
        NeedsItem::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'schedule_id' => $schedule->id,
        ]);

        $response = $this->actingAs($user)->get(route('needs.index'));

        $response->assertInertia(fn ($page) => $page->has('items.0.nextPaymentDate'));
    }

    public function test_index_shows_not_scheduled_for_an_unscheduled_item()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        NeedsItem::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'date_due' => null,
        ]);

        $response = $this->actingAs($user)->get(route('needs.index'));

        $response->assertInertia(fn ($page) => $page->where('items.0.nextPaymentDate', null));
    }

    public function test_index_sorts_by_name()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        NeedsItem::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Zebra',
        ]);
        NeedsItem::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Apple',
        ]);

        $response = $this->actingAs($user)->get(
            route('needs.index', ['sort' => 'name', 'direction' => 'asc']),
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
        NeedsItem::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Zebra',
        ]);
        NeedsItem::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Apple',
        ]);

        $response = $this->actingAs($user)->get(
            route('needs.index', ['sort' => 'name', 'direction' => 'desc']),
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
        NeedsItem::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 50000,
        ]);
        NeedsItem::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 5000,
        ]);

        $response = $this->actingAs($user)->get(
            route('needs.index', ['sort' => 'amount', 'direction' => 'asc']),
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
        NeedsItem::factory()->create([
            'user_id' => $user->id,
            'category_id' => $zCategory->id,
            'name' => 'First',
        ]);
        NeedsItem::factory()->create([
            'user_id' => $user->id,
            'category_id' => $aCategory->id,
            'name' => 'Second',
        ]);

        $response = $this->actingAs($user)->get(
            route('needs.index', ['sort' => 'category', 'direction' => 'asc']),
        );

        $response->assertInertia(fn ($page) => $page
            ->where('items.0.category', 'Aaa Category')
            ->where('items.1.category', 'Zzz Category'),
        );
    }

    public function test_index_falls_back_to_name_for_an_invalid_sort_column()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        NeedsItem::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user)->get(route('needs.index', ['sort' => 'notes']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('sort', 'name'));
    }

    public function test_index_defaults_to_name_ascending_when_no_sort_is_given()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        NeedsItem::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Zebra',
        ]);
        NeedsItem::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Apple',
        ]);

        $response = $this->actingAs($user)->get(route('needs.index'));

        $response->assertInertia(fn ($page) => $page
            ->where('items.0.name', 'Apple')
            ->where('items.1.name', 'Zebra')
            ->where('sort', 'name')
            ->where('direction', 'asc'),
        );
    }
}
