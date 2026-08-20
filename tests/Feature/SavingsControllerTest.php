<?php

namespace Tests\Feature;

use App\Enums\MonthlyRecurrence;
use App\Enums\SavingsItemStatus;
use App\Enums\SavingsItemType;
use App\Models\SavingsItem;
use App\Models\Schedule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('savings.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_index_only_returns_the_current_users_items()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        SavingsItem::factory()->create([
            'user_id' => $user->id,
            'name' => 'My fund',
        ]);
        SavingsItem::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'Their fund',
        ]);

        $response = $this->actingAs($user)->get(route('savings.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Savings/Index')
            ->has('items', 1)
            ->where('items.0.name', 'My fund'),
        );
    }

    public function test_store_creates_a_savings_item_with_a_recurring_schedule()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('savings.store'), [
            'type' => 'savings',
            'name' => 'Emergency fund',
            'target_amount' => 500000,
            'installment_amount' => 25000,
            'target_profit' => 10000,
            'status' => 'ongoing',
            'schedule' => ['recurrence' => 'monthly', 'start_date' => '2026-03-01'],
        ]);

        $item = SavingsItem::where('name', 'Emergency fund')->firstOrFail();

        $this->assertSame(SavingsItemType::SAVINGS, $item->type);
        $this->assertSame('500000.00', $item->target_amount);
        $this->assertSame('25000.00', $item->installment_amount);
        $this->assertSame('10000.00', $item->target_profit);
        $this->assertSame(0, $item->installments_made);
        $this->assertSame(SavingsItemStatus::ONGOING, $item->status);
        $this->assertNotNull($item->schedule_id);
        $this->assertSame(MonthlyRecurrence::Monthly, $item->schedule->recurrence);
    }

    public function test_store_creates_an_investment_with_a_quarterly_schedule()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('savings.store'), [
            'type' => 'investment',
            'name' => 'Index fund',
            'target_amount' => 1000000,
            'installment_amount' => 100000,
            'status' => 'pending',
            'schedule' => ['recurrence' => 'quarterly', 'start_date' => '2026-01-01'],
        ]);

        $item = SavingsItem::where('name', 'Index fund')->firstOrFail();

        $this->assertSame(SavingsItemType::INVESTMENT, $item->type);
        $this->assertSame(MonthlyRecurrence::Quarterly, $item->schedule->recurrence);
    }

    public function test_store_allows_an_omitted_target_profit_and_maturity_date()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('savings.store'), [
            'type' => 'savings',
            'name' => 'No frills fund',
            'target_amount' => 100000,
            'installment_amount' => 10000,
            'status' => 'pending',
            'schedule' => ['recurrence' => 'monthly', 'start_date' => '2026-03-01'],
        ]);

        $item = SavingsItem::where('name', 'No frills fund')->firstOrFail();

        $this->assertNull($item->target_profit);
        $this->assertNull($item->maturity_date);
    }

    public function test_store_validates_the_type_is_a_known_value()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('savings.store'), [
            'type' => 'not-a-real-type',
            'name' => 'Bad type',
            'target_amount' => 1000,
            'installment_amount' => 100,
            'status' => 'pending',
        ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_store_requires_a_schedule()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('savings.store'), [
            'type' => 'savings',
            'name' => 'No schedule fund',
            'target_amount' => 1000,
            'installment_amount' => 100,
            'status' => 'pending',
        ]);

        $response->assertSessionHasErrors('schedule');
        $this->assertDatabaseMissing('savings_items', ['name' => 'No schedule fund']);
    }

    public function test_update_changes_the_items_fields()
    {
        $user = User::factory()->create();
        $schedule = Schedule::factory()->create(['is_active' => true]);
        $item = SavingsItem::factory()->create([
            'user_id' => $user->id,
            'schedule_id' => $schedule->id,
            'type' => SavingsItemType::SAVINGS,
            'target_amount' => 100000,
        ]);

        $this->actingAs($user)->put(route('savings.update', $item), [
            'type' => 'investment',
            'name' => $item->name,
            'target_amount' => 150000,
            'installment_amount' => $item->installment_amount,
            'status' => 'ongoing',
            'schedule' => ['recurrence' => 'monthly', 'start_date' => '2026-03-01'],
        ]);

        $item->refresh();

        $this->assertSame('150000.00', $item->target_amount);
        $this->assertSame(SavingsItemType::INVESTMENT, $item->type);
        $this->assertSame(SavingsItemStatus::ONGOING, $item->status);
    }

    public function test_update_requires_a_schedule()
    {
        $user = User::factory()->create();
        $schedule = Schedule::factory()->create(['is_active' => true]);
        $item = SavingsItem::factory()->create([
            'user_id' => $user->id,
            'schedule_id' => $schedule->id,
        ]);

        $response = $this->actingAs($user)->put(route('savings.update', $item), [
            'type' => $item->type->value,
            'name' => $item->name,
            'target_amount' => $item->target_amount,
            'installment_amount' => $item->installment_amount,
            'status' => $item->status->value,
        ]);

        $response->assertSessionHasErrors('schedule');
        $this->assertSame($schedule->id, $item->fresh()->schedule_id);
        $this->assertDatabaseHas('schedules', ['id' => $schedule->id]);
    }

    public function test_a_user_cannot_update_another_users_item()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $item = SavingsItem::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->put(route('savings.update', $item), [
            'type' => 'savings',
            'name' => 'Hijacked',
            'target_amount' => 1,
            'installment_amount' => 1,
            'status' => 'pending',
        ]);

        $response->assertForbidden();
    }

    public function test_destroy_removes_the_item()
    {
        $user = User::factory()->create();
        $item = SavingsItem::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('savings.destroy', $item));

        $response->assertRedirect();
        $this->assertDatabaseMissing('savings_items', ['id' => $item->id]);
    }

    public function test_a_user_cannot_destroy_another_users_item()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $item = SavingsItem::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->delete(route('savings.destroy', $item));

        $response->assertForbidden();
        $this->assertDatabaseHas('savings_items', ['id' => $item->id]);
    }

    public function test_archiving_an_item_hides_it_from_the_default_index()
    {
        $user = User::factory()->create();
        $item = SavingsItem::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->patch(route('savings.status', $item), ['status' => 'archived']);

        $this->assertSame(SavingsItemStatus::ARCHIVED, $item->fresh()->status);

        $response = $this->actingAs($user)->get(route('savings.index'));

        $response->assertInertia(fn ($page) => $page->has('items', 0));
    }

    public function test_show_archived_includes_archived_items_in_the_same_list()
    {
        $user = User::factory()->create();

        SavingsItem::factory()->create([
            'user_id' => $user->id,
            'name' => 'Active fund',
            'status' => SavingsItemStatus::PENDING,
        ]);
        SavingsItem::factory()->create([
            'user_id' => $user->id,
            'name' => 'Archived fund',
            'status' => SavingsItemStatus::ARCHIVED,
        ]);

        $response = $this->actingAs($user)->get(route('savings.index', ['show_archived' => 1]));

        $response->assertInertia(fn ($page) => $page
            ->has('items', 2)
            ->where('items.0.name', 'Active fund')
            ->where('items.1.name', 'Archived fund')
            ->where('showArchived', true),
        );
    }

    public function test_restoring_an_item_sets_it_back_to_pending()
    {
        $user = User::factory()->create();
        $item = SavingsItem::factory()->create([
            'user_id' => $user->id,
            'status' => SavingsItemStatus::ARCHIVED,
        ]);

        $response = $this->actingAs($user)->patch(route('savings.status', $item), ['status' => 'pending']);

        $response->assertRedirect();
        $this->assertSame(SavingsItemStatus::PENDING, $item->fresh()->status);
    }

    public function test_index_sorts_by_amount()
    {
        $user = User::factory()->create();
        SavingsItem::factory()->create([
            'user_id' => $user->id,
            'installment_amount' => 50000,
        ]);
        SavingsItem::factory()->create([
            'user_id' => $user->id,
            'installment_amount' => 5000,
        ]);

        $response = $this->actingAs($user)->get(
            route('savings.index', ['sort' => 'amount', 'direction' => 'asc']),
        );

        $response->assertInertia(fn ($page) => $page
            ->where('items.0.installmentAmount', 5000)
            ->where('items.1.installmentAmount', 50000),
        );
    }

    public function test_record_contribution_increments_installments_made_and_sets_the_last_contribution_date()
    {
        $user = User::factory()->create();
        $item = SavingsItem::factory()->create([
            'user_id' => $user->id,
            'target_amount' => 1000000,
            'installment_amount' => 10000,
            'installments_made' => 3,
        ]);

        $response = $this->actingAs($user)->patch(route('savings.contribution', $item));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertSame(4, $item->fresh()->installments_made);
        $this->assertSame(CarbonImmutable::today()->toDateString(), $item->fresh()->last_contribution_date->toDateString());
    }

    public function test_record_contribution_is_rejected_when_already_contributed_for_the_current_period()
    {
        $user = User::factory()->create();
        $schedule = Schedule::factory()->create(['recurrence' => MonthlyRecurrence::Monthly, 'start_date' => '2026-01-15']);
        $item = SavingsItem::factory()->create([
            'user_id' => $user->id,
            'schedule_id' => $schedule->id,
            'target_amount' => 1000000,
            'installment_amount' => 10000,
            'installments_made' => 3,
            'last_contribution_date' => CarbonImmutable::today()->toDateString(),
        ]);

        $response = $this->actingAs($user)->patch(route('savings.contribution', $item));

        $response->assertSessionHasErrors('contribution');
        $this->assertSame(3, $item->fresh()->installments_made);
    }

    public function test_record_contribution_is_rejected_once_completed()
    {
        $user = User::factory()->create();
        $item = SavingsItem::factory()->create([
            'user_id' => $user->id,
            'target_amount' => 10000,
            'installment_amount' => 10000,
            'installments_made' => 1,
            'status' => SavingsItemStatus::COMPLETED,
        ]);

        $response = $this->actingAs($user)->patch(route('savings.contribution', $item));

        $response->assertSessionHasErrors('contribution');
        $this->assertSame(1, $item->fresh()->installments_made);
    }

    public function test_record_contribution_auto_completes_the_item_once_the_target_is_reached()
    {
        $user = User::factory()->create();
        $item = SavingsItem::factory()->create([
            'user_id' => $user->id,
            'target_amount' => 10000,
            'installment_amount' => 10000,
            'installments_made' => 0,
            'status' => SavingsItemStatus::PENDING,
        ]);

        $this->actingAs($user)->patch(route('savings.contribution', $item));

        $this->assertSame(SavingsItemStatus::COMPLETED, $item->fresh()->status);
    }

    public function test_a_user_cannot_record_a_contribution_for_another_users_item()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $item = SavingsItem::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->patch(route('savings.contribution', $item));

        $response->assertForbidden();
    }

    public function test_index_includes_the_can_record_contribution_flag()
    {
        $user = User::factory()->create();
        SavingsItem::factory()->create([
            'user_id' => $user->id,
            'installments_made' => 0,
            'last_contribution_date' => null,
            'status' => SavingsItemStatus::PENDING,
        ]);

        $response = $this->actingAs($user)->get(route('savings.index'));

        $response->assertInertia(fn ($page) => $page->where('items.0.canRecordContribution', true));
    }

    public function test_index_includes_the_computed_progress_fields()
    {
        $user = User::factory()->create();
        SavingsItem::factory()->create([
            'user_id' => $user->id,
            'target_amount' => 100000,
            'installment_amount' => 10000,
            'installments_made' => 5,
            'target_profit' => 20000,
        ]);

        $response = $this->actingAs($user)->get(route('savings.index'));

        $response->assertInertia(fn ($page) => $page
            ->where('items.0.amountSaved', 50000)
            ->where('items.0.remainingToTarget', 50000)
            ->where('items.0.profitEarned', 10000),
        );
    }
}
