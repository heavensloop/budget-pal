<?php

namespace Tests\Unit\Models;

use App\Actions\Budget\RecordDebtPayment;
use App\Enums\DebtItemStatus;
use App\Enums\MonthlyRecurrence;
use App\Models\DebtItem;
use App\Models\Schedule;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebtItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_debt_items_cannot_share_the_same_schedule()
    {
        $schedule = Schedule::factory()->create();
        DebtItem::factory()->create(['schedule_id' => $schedule->id]);

        $this->expectException(QueryException::class);

        DebtItem::factory()->create(['schedule_id' => $schedule->id]);
    }

    public function test_monthly_debt_item_returns_the_next_due_date()
    {
        $schedule = Schedule::factory()->create(['recurrence' => MonthlyRecurrence::Monthly, 'start_date' => '2026-01-15']);
        $item = DebtItem::factory()->create(['schedule_id' => $schedule->id]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 3, 10));

        $this->assertSame('2026-03-15', $nextPaymentDate->toDateString());
    }

    public function test_one_time_debt_item_returns_its_due_date()
    {
        $schedule = Schedule::factory()->create(['recurrence' => null, 'start_date' => '2026-09-15']);
        $item = DebtItem::factory()->create(['schedule_id' => $schedule->id]);

        $nextPaymentDate = $item->nextPaymentDate(CarbonImmutable::create(2026, 3, 10));

        $this->assertSame('2026-09-15', $nextPaymentDate->toDateString());
    }

    public function test_has_paid_current_period_is_false_when_no_payment_has_been_recorded()
    {
        $schedule = Schedule::factory()->create(['recurrence' => MonthlyRecurrence::Monthly, 'start_date' => '2026-01-15']);
        $item = DebtItem::factory()->create(['schedule_id' => $schedule->id, 'last_payment_date' => null]);

        $this->assertFalse($item->hasPaidCurrentPeriod());
    }

    public function test_has_paid_current_period_is_true_within_the_same_period_a_payment_was_made()
    {
        $schedule = Schedule::factory()->create(['recurrence' => MonthlyRecurrence::Monthly, 'start_date' => '2026-01-15']);
        $item = DebtItem::factory()->create(['schedule_id' => $schedule->id, 'last_payment_date' => '2026-01-16']);

        CarbonImmutable::setTestNow('2026-01-20');

        $this->assertTrue($item->hasPaidCurrentPeriod());

        CarbonImmutable::setTestNow();
    }

    public function test_has_paid_current_period_is_false_once_a_new_period_has_started()
    {
        $schedule = Schedule::factory()->create(['recurrence' => MonthlyRecurrence::Monthly, 'start_date' => '2026-01-15']);
        $item = DebtItem::factory()->create(['schedule_id' => $schedule->id, 'last_payment_date' => '2026-01-16']);

        CarbonImmutable::setTestNow('2026-02-16');

        $this->assertFalse($item->hasPaidCurrentPeriod());

        CarbonImmutable::setTestNow();
    }

    public function test_has_paid_current_period_stays_true_for_a_one_time_debt_once_any_payment_is_recorded()
    {
        $schedule = Schedule::factory()->create(['recurrence' => null, 'start_date' => '2026-01-15']);
        $item = DebtItem::factory()->create(['schedule_id' => $schedule->id, 'last_payment_date' => '2026-01-15']);

        $this->assertTrue($item->hasPaidCurrentPeriod());
    }

    public function test_balance_is_total_repayment_minus_what_has_been_paid_so_far()
    {
        $item = DebtItem::factory()->create([
            'total_repayment_amount' => 120000,
            'monthly_repayment_amount' => 10000,
            'tenure_months' => 12,
            'payments_made' => 3,
        ]);

        $this->assertSame(90000.0, $item->balance());
    }

    public function test_balance_is_clamped_at_zero()
    {
        $item = DebtItem::factory()->create([
            'total_repayment_amount' => 10000,
            'monthly_repayment_amount' => 10000,
            'tenure_months' => 1,
            'payments_made' => 1,
        ]);

        $this->assertSame(0.0, $item->balance());
    }

    public function test_remaining_tenure_is_tenure_minus_payments_made()
    {
        $item = DebtItem::factory()->create(['tenure_months' => 12, 'payments_made' => 5]);

        $this->assertSame(7, $item->remainingTenure());
    }

    public function test_interest_monthly_spreads_the_total_interest_across_the_tenure()
    {
        $item = DebtItem::factory()->create([
            'amount_borrowed' => 100000,
            'total_repayment_amount' => 124000,
            'tenure_months' => 12,
        ]);

        $this->assertSame(2000.0, $item->interestMonthly());
    }

    public function test_interest_rate_is_the_monthly_interest_as_a_percentage_of_amount_borrowed()
    {
        $item = DebtItem::factory()->create([
            'amount_borrowed' => 100000,
            'total_repayment_amount' => 124000,
            'tenure_months' => 12,
        ]);

        $this->assertSame(2.0, $item->interestRate());
    }

    public function test_record_debt_payment_increments_payments_made()
    {
        $item = DebtItem::factory()->create(['tenure_months' => 12, 'payments_made' => 3]);

        $item = (new RecordDebtPayment)($item);

        $this->assertSame(4, $item->payments_made);
    }

    public function test_record_debt_payment_does_not_exceed_the_tenure()
    {
        $item = DebtItem::factory()->create(['tenure_months' => 5, 'payments_made' => 5]);

        $item = (new RecordDebtPayment)($item);

        $this->assertSame(5, $item->payments_made);
    }

    public function test_record_debt_payment_auto_archives_the_debt_once_the_tenure_is_complete()
    {
        $item = DebtItem::factory()->create(['tenure_months' => 1, 'payments_made' => 0, 'status' => DebtItemStatus::Pending]);

        $item = (new RecordDebtPayment)($item);

        $this->assertSame(DebtItemStatus::Archived, $item->status);
    }

    public function test_record_debt_payment_does_not_archive_while_payments_remain()
    {
        $item = DebtItem::factory()->create(['tenure_months' => 12, 'payments_made' => 3, 'status' => DebtItemStatus::Pending]);

        $item = (new RecordDebtPayment)($item);

        $this->assertSame(DebtItemStatus::Pending, $item->status);
    }

    public function test_record_debt_payment_sets_the_last_payment_date_to_today()
    {
        $item = DebtItem::factory()->create(['tenure_months' => 12, 'payments_made' => 0, 'last_payment_date' => null]);

        $item = (new RecordDebtPayment)($item);

        $this->assertSame(CarbonImmutable::today()->toDateString(), $item->last_payment_date->toDateString());
    }
}
