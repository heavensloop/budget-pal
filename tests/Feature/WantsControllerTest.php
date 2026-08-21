<?php

namespace Tests\Feature;

use App\Enums\WantCategory;
use App\Enums\WantItemStatus;
use App\Models\User;
use App\Models\WantItem;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WantsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('wants.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_index_only_returns_the_current_users_items()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        WantItem::factory()->create(['user_id' => $user->id, 'name' => 'My phone']);
        WantItem::factory()->create(['user_id' => $otherUser->id, 'name' => 'Their phone']);

        $response = $this->actingAs($user)->get(route('wants.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Wants/Index')
            ->has('items', 1)
            ->where('items.0.name', 'My phone'),
        );
    }

    public function test_index_defaults_to_position_order()
    {
        $user = User::factory()->create();
        WantItem::factory()->create(['user_id' => $user->id, 'name' => 'Second', 'position' => 2]);
        WantItem::factory()->create(['user_id' => $user->id, 'name' => 'First', 'position' => 1]);

        $response = $this->actingAs($user)->get(route('wants.index'));

        $response->assertInertia(fn ($page) => $page
            ->where('items.0.name', 'First')
            ->where('items.1.name', 'Second'),
        );
    }

    public function test_store_creates_a_want()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('wants.store'), [
            'name' => 'New phone',
            'category' => 'electronics',
            'amount' => 500000,
            'notes' => 'Prefer the Pro model',
        ]);

        $item = WantItem::where('name', 'New phone')->firstOrFail();

        $this->assertSame(WantCategory::ELECTRONICS, $item->category);
        $this->assertSame('500000.00', $item->amount);
        $this->assertSame(WantItemStatus::PLANNED, $item->status);
        $this->assertSame('Prefer the Pro model', $item->notes);
    }

    public function test_store_appends_to_the_end_of_the_priority_list()
    {
        $user = User::factory()->create();
        WantItem::factory()->create(['user_id' => $user->id, 'position' => 5]);

        $this->actingAs($user)->post(route('wants.store'), [
            'name' => 'New phone',
            'category' => 'electronics',
            'amount' => 500000,
        ]);

        $item = WantItem::where('name', 'New phone')->firstOrFail();

        $this->assertSame(6, $item->position);
    }

    public function test_store_validates_the_category_is_a_known_value()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('wants.store'), [
            'name' => 'Bad category',
            'category' => 'not-a-real-category',
            'amount' => 1000,
        ]);

        $response->assertSessionHasErrors('category');
    }

    public function test_store_validates_amount_is_numeric_and_non_negative()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('wants.store'), [
            'name' => 'Bad amount',
            'category' => 'electronics',
            'amount' => -100,
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseMissing('want_items', ['name' => 'Bad amount']);
    }

    public function test_update_changes_the_items_fields()
    {
        $user = User::factory()->create();
        $item = WantItem::factory()->create(['user_id' => $user->id, 'category' => WantCategory::ELECTRONICS]);

        $this->actingAs($user)->put(route('wants.update', $item), [
            'name' => $item->name,
            'category' => 'clothing',
            'amount' => 75000,
        ]);

        $item->refresh();

        $this->assertSame('75000.00', $item->amount);
        $this->assertSame(WantCategory::CLOTHING, $item->category);
    }

    public function test_a_user_cannot_update_another_users_item()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $item = WantItem::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->put(route('wants.update', $item), [
            'name' => 'Hijacked',
            'category' => 'electronics',
            'amount' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_destroy_removes_the_item()
    {
        $user = User::factory()->create();
        $item = WantItem::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('wants.destroy', $item));

        $response->assertRedirect();
        $this->assertDatabaseMissing('want_items', ['id' => $item->id]);
    }

    public function test_a_user_cannot_destroy_another_users_item()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $item = WantItem::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->delete(route('wants.destroy', $item));

        $response->assertForbidden();
        $this->assertDatabaseHas('want_items', ['id' => $item->id]);
    }

    public function test_marking_an_item_purchased_sets_the_status_and_purchased_at()
    {
        $user = User::factory()->create();
        $item = WantItem::factory()->create(['user_id' => $user->id, 'status' => WantItemStatus::PLANNED]);

        $response = $this->actingAs($user)->patch(route('wants.status', $item), ['status' => 'purchased']);

        $response->assertRedirect();
        $this->assertSame(WantItemStatus::PURCHASED, $item->fresh()->status);
        $this->assertSame(CarbonImmutable::today()->toDateString(), $item->fresh()->purchased_at->toDateString());
    }

    public function test_archiving_an_item_hides_it_from_the_default_index()
    {
        $user = User::factory()->create();
        $item = WantItem::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->patch(route('wants.status', $item), ['status' => 'archived']);

        $this->assertSame(WantItemStatus::ARCHIVED, $item->fresh()->status);

        $response = $this->actingAs($user)->get(route('wants.index'));

        $response->assertInertia(fn ($page) => $page->has('items', 0));
    }

    public function test_show_archived_includes_archived_items_in_the_same_list()
    {
        $user = User::factory()->create();

        WantItem::factory()->create(['user_id' => $user->id, 'name' => 'Active want', 'position' => 1, 'status' => WantItemStatus::PLANNED]);
        WantItem::factory()->create(['user_id' => $user->id, 'name' => 'Archived want', 'position' => 2, 'status' => WantItemStatus::ARCHIVED]);

        $response = $this->actingAs($user)->get(route('wants.index', ['show_archived' => 1]));

        $response->assertInertia(fn ($page) => $page
            ->has('items', 2)
            ->where('items.0.name', 'Active want')
            ->where('items.1.name', 'Archived want')
            ->where('showArchived', true),
        );
    }

    public function test_restoring_an_item_sets_it_back_to_planned()
    {
        $user = User::factory()->create();
        $item = WantItem::factory()->create(['user_id' => $user->id, 'status' => WantItemStatus::ARCHIVED]);

        $response = $this->actingAs($user)->patch(route('wants.status', $item), ['status' => 'planned']);

        $response->assertRedirect();
        $this->assertSame(WantItemStatus::PLANNED, $item->fresh()->status);
    }

    public function test_reorder_moves_an_item_up()
    {
        $user = User::factory()->create();
        $first = WantItem::factory()->create(['user_id' => $user->id, 'position' => 1, 'status' => WantItemStatus::PLANNED]);
        $second = WantItem::factory()->create(['user_id' => $user->id, 'position' => 2, 'status' => WantItemStatus::PLANNED]);

        $response = $this->actingAs($user)->patch(route('wants.reorder', $second), ['direction' => 'up']);

        $response->assertRedirect();
        $this->assertSame(2, $first->fresh()->position);
        $this->assertSame(1, $second->fresh()->position);
    }

    public function test_a_user_cannot_reorder_another_users_item()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $item = WantItem::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->patch(route('wants.reorder', $item), ['direction' => 'up']);

        $response->assertForbidden();
    }

    public function test_index_includes_purchases_made_this_month_regardless_of_current_status()
    {
        $user = User::factory()->create();
        WantItem::factory()->create([
            'user_id' => $user->id,
            'amount' => 20000,
            'status' => WantItemStatus::ARCHIVED,
            'purchased_at' => CarbonImmutable::today()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('wants.index', ['show_archived' => 1]));

        $response->assertInertia(fn ($page) => $page
            ->where('items.0.purchasedAt', CarbonImmutable::today()->toDateString())
            ->where('items.0.status', 'archived'),
        );
    }
}
