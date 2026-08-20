<?php

namespace Tests\Feature;

use App\Enums\DebtItemStatus;
use App\Enums\LoanCategory;
use App\Enums\MonthlyRecurrence;
use App\Models\DebtItem;
use App\Models\Schedule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebtsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('debts.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_index_only_returns_the_current_users_items()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        DebtItem::factory()->create([
            'user_id' => $user->id,
            'name' => 'My loan',
        ]);
        DebtItem::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'Their loan',
        ]);

        $response = $this->actingAs($user)->get(route('debts.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Debts/Index')
            ->has('items', 1)
            ->where('items.0.name', 'My loan'),
        );
    }

    public function test_store_creates_a_debt_with_a_recurring_schedule()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('debts.store'), [
            'category' => 'auto',
            'name' => 'Car loan',
            'amount_borrowed' => 500000,
            'total_repayment_amount' => 560000,
            'monthly_repayment_amount' => 25000,
            'tenure_months' => 24,
            'schedule' => ['recurrence' => 'monthly', 'start_date' => '2026-03-01'],
        ]);

        $item = DebtItem::where('name', 'Car loan')->firstOrFail();

        $this->assertSame(LoanCategory::Auto, $item->category);
        $this->assertSame('500000.00', $item->amount_borrowed);
        $this->assertSame('560000.00', $item->total_repayment_amount);
        $this->assertSame('25000.00', $item->monthly_repayment_amount);
        $this->assertSame(24, $item->tenure_months);
        $this->assertSame(0, $item->payments_made);
        $this->assertNotNull($item->schedule_id);
        $this->assertSame(MonthlyRecurrence::Monthly, $item->schedule->recurrence);
    }

    public function test_store_accepts_an_initial_payments_made_count()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('debts.store'), [
            'category' => 'personal',
            'name' => 'Pre-existing loan',
            'amount_borrowed' => 200000,
            'total_repayment_amount' => 200000,
            'monthly_repayment_amount' => 20000,
            'tenure_months' => 10,
            'payments_made' => 3,
        ]);

        $item = DebtItem::where('name', 'Pre-existing loan')->firstOrFail();

        $this->assertSame(3, $item->payments_made);
    }

    public function test_store_validates_amount_borrowed_is_numeric_and_non_negative()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('debts.store'), [
            'category' => 'personal',
            'name' => 'Bad amount',
            'amount_borrowed' => -100,
            'total_repayment_amount' => 1000,
            'monthly_repayment_amount' => 100,
            'tenure_months' => 10,
        ]);

        $response->assertSessionHasErrors('amount_borrowed');
        $this->assertDatabaseMissing('debt_items', ['name' => 'Bad amount']);
    }

    public function test_store_requires_total_repayment_amount_to_be_at_least_the_amount_borrowed()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('debts.store'), [
            'category' => 'personal',
            'name' => 'Bad total',
            'amount_borrowed' => 100000,
            'total_repayment_amount' => 50000,
            'monthly_repayment_amount' => 10000,
            'tenure_months' => 10,
        ]);

        $response->assertSessionHasErrors('total_repayment_amount');
    }

    public function test_store_validates_the_category_is_a_known_value()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('debts.store'), [
            'category' => 'not-a-real-category',
            'name' => 'Bad category',
            'amount_borrowed' => 1000,
            'total_repayment_amount' => 1000,
            'monthly_repayment_amount' => 100,
            'tenure_months' => 10,
        ]);

        $response->assertSessionHasErrors('category');
    }

    public function test_store_rejects_a_duplicate_one_time_item_name()
    {
        $user = User::factory()->create();
        DebtItem::factory()->create([
            'user_id' => $user->id,
            'schedule_id' => null,
            'name' => 'Loan from Dad',
        ]);

        $response = $this->actingAs($user)->post(route('debts.store'), [
            'category' => 'personal',
            'name' => 'Loan from Dad',
            'amount_borrowed' => 1000,
            'total_repayment_amount' => 1000,
            'monthly_repayment_amount' => 100,
            'tenure_months' => 10,
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame(1, DebtItem::where('name', 'Loan from Dad')->count());
    }

    public function test_update_changes_the_items_fields()
    {
        $user = User::factory()->create();
        $item = DebtItem::factory()->create([
            'user_id' => $user->id,
            'category' => LoanCategory::Personal,
            'amount_borrowed' => 100000,
            'total_repayment_amount' => 100000,
        ]);

        $this->actingAs($user)->put(route('debts.update', $item), [
            'category' => 'credit_card',
            'name' => $item->name,
            'amount_borrowed' => 100000,
            'total_repayment_amount' => 120000,
            'monthly_repayment_amount' => $item->monthly_repayment_amount,
            'tenure_months' => $item->tenure_months,
        ]);

        $item->refresh();

        $this->assertSame('120000.00', $item->total_repayment_amount);
        $this->assertSame(LoanCategory::CreditCard, $item->category);
    }

    public function test_update_without_a_schedule_removes_the_items_existing_schedule()
    {
        $user = User::factory()->create();
        $schedule = Schedule::factory()->create(['is_active' => true]);
        $item = DebtItem::factory()->create([
            'user_id' => $user->id,
            'schedule_id' => $schedule->id,
        ]);

        $this->actingAs($user)->put(route('debts.update', $item), [
            'category' => $item->category->value,
            'name' => $item->name,
            'amount_borrowed' => $item->amount_borrowed,
            'total_repayment_amount' => $item->total_repayment_amount,
            'monthly_repayment_amount' => $item->monthly_repayment_amount,
            'tenure_months' => $item->tenure_months,
        ]);

        $this->assertNull($item->fresh()->schedule_id);
        $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
    }

    public function test_a_user_cannot_update_another_users_item()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $item = DebtItem::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->put(route('debts.update', $item), [
            'category' => 'personal',
            'name' => 'Hijacked',
            'amount_borrowed' => 1,
            'total_repayment_amount' => 1,
            'monthly_repayment_amount' => 1,
            'tenure_months' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_destroy_removes_the_item()
    {
        $user = User::factory()->create();
        $item = DebtItem::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('debts.destroy', $item));

        $response->assertRedirect();
        $this->assertDatabaseMissing('debt_items', ['id' => $item->id]);
    }

    public function test_a_user_cannot_destroy_another_users_item()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $item = DebtItem::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->delete(route('debts.destroy', $item));

        $response->assertForbidden();
        $this->assertDatabaseHas('debt_items', ['id' => $item->id]);
    }

    public function test_archiving_an_item_hides_it_from_the_default_index()
    {
        $user = User::factory()->create();
        $item = DebtItem::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->patch(route('debts.status', $item), ['status' => 'archived']);

        $this->assertSame(DebtItemStatus::Archived, $item->fresh()->status);

        $response = $this->actingAs($user)->get(route('debts.index'));

        $response->assertInertia(fn ($page) => $page->has('items', 0));
    }

    public function test_show_archived_includes_archived_items_in_the_same_list()
    {
        $user = User::factory()->create();

        DebtItem::factory()->create([
            'user_id' => $user->id,
            'name' => 'Active debt',
            'status' => DebtItemStatus::Pending,
        ]);
        DebtItem::factory()->create([
            'user_id' => $user->id,
            'name' => 'Archived debt',
            'status' => DebtItemStatus::Archived,
        ]);

        $response = $this->actingAs($user)->get(route('debts.index', ['show_archived' => 1]));

        $response->assertInertia(fn ($page) => $page
            ->has('items', 2)
            ->where('items.0.name', 'Active debt')
            ->where('items.1.name', 'Archived debt')
            ->where('showArchived', true),
        );
    }

    public function test_restoring_an_item_sets_it_back_to_pending()
    {
        $user = User::factory()->create();
        $item = DebtItem::factory()->create([
            'user_id' => $user->id,
            'status' => DebtItemStatus::Archived,
        ]);

        $response = $this->actingAs($user)->patch(route('debts.status', $item), ['status' => 'pending']);

        $response->assertRedirect();
        $this->assertSame(DebtItemStatus::Pending, $item->fresh()->status);
    }

    public function test_index_sorts_by_balance()
    {
        $user = User::factory()->create();
        DebtItem::factory()->create([
            'user_id' => $user->id,
            'total_repayment_amount' => 50000,
            'monthly_repayment_amount' => 5000,
            'tenure_months' => 10,
            'payments_made' => 0,
        ]);
        DebtItem::factory()->create([
            'user_id' => $user->id,
            'total_repayment_amount' => 5000,
            'monthly_repayment_amount' => 500,
            'tenure_months' => 10,
            'payments_made' => 0,
        ]);

        $response = $this->actingAs($user)->get(
            route('debts.index', ['sort' => 'balance', 'direction' => 'asc']),
        );

        $response->assertInertia(fn ($page) => $page
            ->where('items.0.balance', 5000)
            ->where('items.1.balance', 50000),
        );
    }

    public function test_index_sorts_by_amount()
    {
        $user = User::factory()->create();
        DebtItem::factory()->create([
            'user_id' => $user->id,
            'monthly_repayment_amount' => 50000,
        ]);
        DebtItem::factory()->create([
            'user_id' => $user->id,
            'monthly_repayment_amount' => 5000,
        ]);

        $response = $this->actingAs($user)->get(
            route('debts.index', ['sort' => 'amount', 'direction' => 'asc']),
        );

        $response->assertInertia(fn ($page) => $page
            ->where('items.0.monthlyRepaymentAmount', 5000)
            ->where('items.1.monthlyRepaymentAmount', 50000),
        );
    }

    public function test_record_payment_increments_payments_made_and_sets_the_last_payment_date()
    {
        $user = User::factory()->create();
        $item = DebtItem::factory()->create([
            'user_id' => $user->id,
            'tenure_months' => 12,
            'payments_made' => 3,
        ]);

        $response = $this->actingAs($user)->patch(route('debts.payment', $item));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertSame(4, $item->fresh()->payments_made);
        $this->assertSame(CarbonImmutable::today()->toDateString(), $item->fresh()->last_payment_date->toDateString());
    }

    public function test_record_payment_is_rejected_when_already_paid_for_the_current_period()
    {
        $user = User::factory()->create();
        $schedule = Schedule::factory()->create(['recurrence' => MonthlyRecurrence::Monthly, 'start_date' => '2026-01-15']);
        $item = DebtItem::factory()->create([
            'user_id' => $user->id,
            'schedule_id' => $schedule->id,
            'tenure_months' => 12,
            'payments_made' => 3,
            'last_payment_date' => CarbonImmutable::today()->toDateString(),
        ]);

        $response = $this->actingAs($user)->patch(route('debts.payment', $item));

        $response->assertSessionHasErrors('payment');
        $this->assertSame(3, $item->fresh()->payments_made);
    }

    public function test_record_payment_is_rejected_once_fully_paid_off()
    {
        $user = User::factory()->create();
        $item = DebtItem::factory()->create([
            'user_id' => $user->id,
            'tenure_months' => 5,
            'payments_made' => 5,
        ]);

        $response = $this->actingAs($user)->patch(route('debts.payment', $item));

        $response->assertSessionHasErrors('payment');
        $this->assertSame(5, $item->fresh()->payments_made);
    }

    public function test_record_payment_auto_archives_the_debt_once_fully_paid()
    {
        $user = User::factory()->create();
        $item = DebtItem::factory()->create([
            'user_id' => $user->id,
            'tenure_months' => 1,
            'payments_made' => 0,
            'status' => DebtItemStatus::Pending,
        ]);

        $this->actingAs($user)->patch(route('debts.payment', $item));

        $this->assertSame(DebtItemStatus::Archived, $item->fresh()->status);
    }

    public function test_a_user_cannot_record_a_payment_for_another_users_debt()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $item = DebtItem::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->patch(route('debts.payment', $item));

        $response->assertForbidden();
    }

    public function test_index_includes_the_can_record_payment_flag()
    {
        $user = User::factory()->create();
        DebtItem::factory()->create([
            'user_id' => $user->id,
            'tenure_months' => 10,
            'payments_made' => 0,
            'last_payment_date' => null,
        ]);

        $response = $this->actingAs($user)->get(route('debts.index'));

        $response->assertInertia(fn ($page) => $page->where('items.0.canRecordPayment', true));
    }

    public function test_index_includes_the_computed_interest_fields()
    {
        $user = User::factory()->create();
        DebtItem::factory()->create([
            'user_id' => $user->id,
            'amount_borrowed' => 100000,
            'total_repayment_amount' => 124000,
            'tenure_months' => 12,
            'payments_made' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('debts.index'));

        $response->assertInertia(fn ($page) => $page
            ->where('items.0.interestMonthly', 2000)
            ->where('items.0.interestRate', 2)
            ->where('items.0.balance', 124000)
            ->where('items.0.remainingTenure', 12),
        );
    }
}
