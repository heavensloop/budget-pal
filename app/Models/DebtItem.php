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
 * @property string $principal
 * @property string $balance
 * @property string $amount
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
    'principal',
    'balance',
    'amount',
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
     * Whether a payment has already been recorded for whichever period is
     * currently active, so the "Record Payment" action can be disabled
     * rather than allowing an accidental double paydown. A one-time debt
     * (or one with no schedule at all) has no "next period" to roll into,
     * so any recorded payment blocks further ones. A recurring debt is
     * still "in the paid period" when the next due date computed from the
     * day after the last payment matches the next due date computed from
     * today - i.e. no new period has started since that payment.
     */
    public function hasPaidCurrentPeriod(): bool
    {
        if ($this->last_payment_date === null) {
            return false;
        }

        if (! $this->schedule?->recurrence) {
            return true;
        }

        $fromLastPayment = $this->nextPaymentDate($this->last_payment_date->addDay());
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
            'principal' => 'decimal:2',
            'balance' => 'decimal:2',
            'amount' => 'decimal:2',
            'status' => DebtItemStatus::class,
            'category' => LoanCategory::class,
            'last_payment_date' => 'date',
        ];
    }
}
