<?php

namespace App\Models;

use App\Concerns\IsSchedulableItem;
use App\Enums\DebtItemStatus;
use App\Enums\LoanCategory;
use App\Enums\SortDirection;
use Carbon\CarbonImmutable;
use Database\Factories\DebtItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property LoanCategory $category
 * @property int|null $schedule_id
 * @property string $name
 * @property string $amount_borrowed
 * @property string $total_repayment_amount
 * @property string $monthly_repayment_amount
 * @property int $tenure_months
 * @property int $payments_made
 * @property string $currency_code
 * @property DebtItemStatus $status
 * @property CarbonImmutable|null $last_payment_date
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'category',
    'schedule_id',
    'name',
    'amount_borrowed',
    'total_repayment_amount',
    'monthly_repayment_amount',
    'tenure_months',
    'payments_made',
    'currency_code',
    'status',
    'last_payment_date',
    'notes',
])]
class DebtItem extends Model
{
    /** @use HasFactory<DebtItemFactory> */
    use HasFactory;

    use IsSchedulableItem;

    /**
     * @var list<string>
     */
    public const array SORTABLE_COLUMNS = ['name', 'category', 'amount', 'balance'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The remaining amount left to pay: the total repayment obligation
     * minus what's already been paid off (monthly repayment x payments
     * made). Clamped at 0 so an over-recorded payment count can't go
     * negative.
     */
    public function balance(): float
    {
        $paidSoFar = (float) $this->monthly_repayment_amount * $this->payments_made;

        return max(0.0, (float) $this->total_repayment_amount - $paidSoFar);
    }

    public function remainingTenure(): int
    {
        return max(0, $this->tenure_months - $this->payments_made);
    }

    /**
     * Average monthly interest: total interest (total repayment - amount
     * borrowed) spread evenly across the tenure.
     */
    public function interestMonthly(): float
    {
        if ($this->tenure_months <= 0) {
            return 0.0;
        }

        return ((float) $this->total_repayment_amount - (float) $this->amount_borrowed) / $this->tenure_months;
    }

    /**
     * The average monthly interest as a percentage of the amount borrowed.
     */
    public function interestRate(): float
    {
        if ((float) $this->amount_borrowed <= 0.0) {
            return 0.0;
        }

        return ($this->interestMonthly() / (float) $this->amount_borrowed) * 100;
    }

    /**
     * Whether a payment has already been recorded for whichever period is
     * currently active, so the "Record Payment" action can be disabled
     * rather than allowing an accidental double paydown. A one-time debt
     * (or one with no schedule at all) has no "next period" to roll into,
     * so any recorded payment blocks further ones. A recurring debt is
     * still "in the paid period" when the occurrence the last payment
     * covered (the next due date on/after that payment) matches the
     * occurrence currently due (the next due date on/after today) - i.e.
     * no new period has started since that payment. Comparing from the
     * payment date itself (not the day after) matters: when a payment is
     * recorded exactly on its due date, shifting the reference forward a
     * day would skip past that occurrence and compare against next
     * period's instead, making today's payment look like it covered a
     * different period than it actually did.
     */
    public function hasPaidCurrentPeriod(): bool
    {
        if ($this->last_payment_date === null) {
            return false;
        }

        if (! $this->schedule?->recurrence) {
            return true;
        }

        $fromLastPayment = $this->nextPaymentDate($this->last_payment_date);
        $fromToday = $this->nextPaymentDate(CarbonImmutable::today());

        return $fromLastPayment !== null && $fromToday !== null && $fromLastPayment->equalTo($fromToday);
    }

    /**
     * @param  Builder<DebtItem>  $query
     */
    #[Scope]
    protected function sortable(Builder $query, string $column, SortDirection $direction): void
    {
        if (! in_array($column, self::SORTABLE_COLUMNS, true)) {
            $column = 'name';
        }

        if ($column === 'amount') {
            $query->orderBy('monthly_repayment_amount', $direction->value);

            return;
        }

        if ($column === 'balance') {
            $query->orderByRaw('(total_repayment_amount - (monthly_repayment_amount * payments_made)) '.$direction->value);

            return;
        }

        $query->orderBy($column, $direction->value);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_borrowed' => 'decimal:2',
            'total_repayment_amount' => 'decimal:2',
            'monthly_repayment_amount' => 'decimal:2',
            'tenure_months' => 'integer',
            'payments_made' => 'integer',
            'status' => DebtItemStatus::class,
            'category' => LoanCategory::class,
            'last_payment_date' => 'date',
        ];
    }
}
