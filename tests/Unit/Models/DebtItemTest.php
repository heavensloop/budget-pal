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

    public function test_record_debt_payment_decrements_the_balance()
    {
        $item = DebtItem::factory()->create(['principal' => 100000, 'balance' => 100000, 'amount' => 15000]);

        $item = (new RecordDebtPayment)($item);

        $this->assertSame('85000.00', $item->balance);
    }

    public function test_record_debt_payment_clamps_the_balance_at_zero()
    {
        $item = DebtItem::factory()->create(['principal' => 10000, 'balance' => 10000, 'amount' => 15000]);

        $item = (new RecordDebtPayment)($item);

        $this->assertSame('0.00', $item->balance);
    }

    public function test_record_debt_payment_auto_archives_the_debt_once_the_balance_reaches_zero()
    {
        $item = DebtItem::factory()->create(['principal' => 15000, 'balance' => 15000, 'amount' => 15000, 'status' => DebtItemStatus::Pending]);

        $item = (new RecordDebtPayment)($item);

        $this->assertSame(DebtItemStatus::Archived, $item->status);
    }

    public function test_record_debt_payment_does_not_archive_while_balance_remains()
    {
        $item = DebtItem::factory()->create(['principal' => 100000, 'balance' => 100000, 'amount' => 15000, 'status' => DebtItemStatus::Pending]);

        $item = (new RecordDebtPayment)($item);

        $this->assertSame(DebtItemStatus::Pending, $item->status);
    }

    public function test_record_debt_payment_sets_the_last_payment_date_to_today()
    {
        $item = DebtItem::factory()->create(['balance' => 100000, 'amount' => 15000, 'last_payment_date' => null]);

        $item = (new RecordDebtPayment)($item);

        $this->assertSame(CarbonImmutable::today()->toDateString(), $item->last_payment_date->toDateString());
    }
}
