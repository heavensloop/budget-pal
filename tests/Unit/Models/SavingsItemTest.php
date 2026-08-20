<?php

namespace Tests\Unit\Models;

use App\Actions\Budget\RecordSavingsContribution;
use App\Enums\MonthlyRecurrence;
use App\Enums\SavingsItemStatus;
use App\Models\SavingsItem;
use App\Models\Schedule;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingsItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_savings_items_cannot_share_the_same_schedule()
    {
        $schedule = Schedule::factory()->create();
        SavingsItem::factory()->create(['schedule_id' => $schedule->id]);

        $this->expectException(QueryException::class);

        SavingsItem::factory()->create(['schedule_id' => $schedule->id]);
    }

    public function test_monthly_savings_item_returns_the_next_due_date()
    {
        $schedule = Schedule::factory()->create(['recurrence' => MonthlyRecurrence::Monthly, 'start_date' => '2026-01-15']);
        $item = SavingsItem::factory()->create(['schedule_id' => $schedule->id]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 3, 10));

        $this->assertSame('2026-03-15', $nextPaymentDate->toDateString());
    }

    public function test_quarterly_savings_item_rolls_forward_every_three_months()
    {
        $schedule = Schedule::factory()->create(['recurrence' => MonthlyRecurrence::Quarterly, 'start_date' => '2026-01-15']);
        $item = SavingsItem::factory()->create(['schedule_id' => $schedule->id]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 3, 10));

        $this->assertSame('2026-04-15', $nextPaymentDate->toDateString());
    }

    public function test_yearly_savings_item_rolls_forward_every_twelve_months()
    {
        $schedule = Schedule::factory()->create(['recurrence' => MonthlyRecurrence::Yearly, 'start_date' => '2026-01-15']);
        $item = SavingsItem::factory()->create(['schedule_id' => $schedule->id]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 3, 10));

        $this->assertSame('2027-01-15', $nextPaymentDate->toDateString());
    }

    public function test_has_contributed_current_period_is_false_when_no_contribution_has_been_recorded()
    {
        $schedule = Schedule::factory()->create(['recurrence' => MonthlyRecurrence::Monthly, 'start_date' => '2026-01-15']);
        $item = SavingsItem::factory()->create(['schedule_id' => $schedule->id, 'last_contribution_date' => null]);

        $this->assertFalse($item->hasContributedCurrentPeriod());
    }

    public function test_has_contributed_current_period_is_true_within_the_same_period_a_contribution_was_made()
    {
        $schedule = Schedule::factory()->create(['recurrence' => MonthlyRecurrence::Monthly, 'start_date' => '2026-01-15']);
        $item = SavingsItem::factory()->create(['schedule_id' => $schedule->id, 'last_contribution_date' => '2026-01-16']);

        CarbonImmutable::setTestNow('2026-01-20');

        $this->assertTrue($item->hasContributedCurrentPeriod());

        CarbonImmutable::setTestNow();
    }

    public function test_has_contributed_current_period_is_false_once_a_new_period_has_started()
    {
        $schedule = Schedule::factory()->create(['recurrence' => MonthlyRecurrence::Monthly, 'start_date' => '2026-01-15']);
        $item = SavingsItem::factory()->create(['schedule_id' => $schedule->id, 'last_contribution_date' => '2026-01-16']);

        CarbonImmutable::setTestNow('2026-02-16');

        $this->assertFalse($item->hasContributedCurrentPeriod());

        CarbonImmutable::setTestNow();
    }

    public function test_has_contributed_current_period_is_true_when_contribution_was_recorded_exactly_on_the_due_date()
    {
        // Regression: see DebtItemTest's equivalent test - comparing from
        // last_contribution_date + 1 day used to skip past today's
        // occurrence, falsely allowing a second contribution the same day
        // whenever the contribution landed exactly on the schedule's day
        // of month (the common case now that Monthly schedules default
        // their day to today's).
        $schedule = Schedule::factory()->create(['recurrence' => MonthlyRecurrence::Monthly, 'start_date' => '2026-01-15']);
        $item = SavingsItem::factory()->create(['schedule_id' => $schedule->id, 'last_contribution_date' => '2026-01-15']);

        CarbonImmutable::setTestNow('2026-01-15');

        $this->assertTrue($item->hasContributedCurrentPeriod());

        CarbonImmutable::setTestNow();
    }

    public function test_amount_saved_is_installment_amount_times_installments_made()
    {
        $item = SavingsItem::factory()->create([
            'target_amount' => 120000,
            'installment_amount' => 10000,
            'installments_made' => 3,
        ]);

        $this->assertSame(30000.0, $item->amountSaved());
    }

    public function test_amount_saved_is_clamped_at_the_target_amount()
    {
        $item = SavingsItem::factory()->create([
            'target_amount' => 20000,
            'installment_amount' => 10000,
            'installments_made' => 5,
        ]);

        $this->assertSame(20000.0, $item->amountSaved());
    }

    public function test_remaining_to_target_is_target_minus_amount_saved()
    {
        $item = SavingsItem::factory()->create([
            'target_amount' => 120000,
            'installment_amount' => 10000,
            'installments_made' => 3,
        ]);

        $this->assertSame(90000.0, $item->remainingToTarget());
    }

    public function test_profit_earned_is_proportional_to_progress_toward_the_target()
    {
        $item = SavingsItem::factory()->create([
            'target_amount' => 100000,
            'installment_amount' => 10000,
            'installments_made' => 5,
            'target_profit' => 20000,
        ]);

        $this->assertSame(10000.0, $item->profitEarned());
    }

    public function test_profit_earned_is_zero_when_no_target_profit_is_set()
    {
        $item = SavingsItem::factory()->create([
            'target_amount' => 100000,
            'installment_amount' => 10000,
            'installments_made' => 5,
            'target_profit' => null,
        ]);

        $this->assertSame(0.0, $item->profitEarned());
    }

    public function test_profit_earned_is_zero_when_the_target_amount_is_zero()
    {
        $item = SavingsItem::factory()->create([
            'target_amount' => 0,
            'installment_amount' => 10000,
            'installments_made' => 5,
            'target_profit' => 20000,
        ]);

        $this->assertSame(0.0, $item->profitEarned());
    }

    public function test_record_savings_contribution_increments_installments_made()
    {
        $item = SavingsItem::factory()->create(['target_amount' => 1000000, 'installment_amount' => 10000, 'installments_made' => 3]);

        $item = (new RecordSavingsContribution)($item);

        $this->assertSame(4, $item->installments_made);
    }

    public function test_record_savings_contribution_does_not_cap_installments_made()
    {
        $item = SavingsItem::factory()->create(['target_amount' => 1000000, 'installment_amount' => 10000, 'installments_made' => 99]);

        $item = (new RecordSavingsContribution)($item);

        $this->assertSame(100, $item->installments_made);
    }

    public function test_record_savings_contribution_auto_completes_once_the_target_is_reached()
    {
        $item = SavingsItem::factory()->create([
            'target_amount' => 10000,
            'installment_amount' => 10000,
            'installments_made' => 0,
            'status' => SavingsItemStatus::PENDING,
        ]);

        $item = (new RecordSavingsContribution)($item);

        $this->assertSame(SavingsItemStatus::COMPLETED, $item->status);
    }

    public function test_record_savings_contribution_does_not_complete_while_under_target()
    {
        $item = SavingsItem::factory()->create([
            'target_amount' => 120000,
            'installment_amount' => 10000,
            'installments_made' => 3,
            'status' => SavingsItemStatus::ONGOING,
        ]);

        $item = (new RecordSavingsContribution)($item);

        $this->assertSame(SavingsItemStatus::ONGOING, $item->status);
    }

    public function test_record_savings_contribution_sets_the_last_contribution_date_to_today()
    {
        $item = SavingsItem::factory()->create(['target_amount' => 1000000, 'installment_amount' => 10000, 'installments_made' => 0, 'last_contribution_date' => null]);

        $item = (new RecordSavingsContribution)($item);

        $this->assertSame(CarbonImmutable::today()->toDateString(), $item->last_contribution_date->toDateString());
    }
}
