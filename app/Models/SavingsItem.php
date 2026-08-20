<?php

namespace App\Models;

use App\Concerns\IsSchedulableItem;
use App\Enums\SavingsItemStatus;
use App\Enums\SavingsItemType;
use App\Enums\SortDirection;
use Carbon\CarbonImmutable;
use Database\Factories\SavingsItemFactory;
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
 * @property SavingsItemType $type
 * @property int|null $schedule_id
 * @property string $name
 * @property string $target_amount
 * @property string $installment_amount
 * @property int $installments_made
 * @property string|null $target_profit
 * @property CarbonImmutable|null $maturity_date
 * @property string $currency_code
 * @property SavingsItemStatus $status
 * @property CarbonImmutable|null $last_contribution_date
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'type',
    'schedule_id',
    'name',
    'target_amount',
    'installment_amount',
    'installments_made',
    'target_profit',
    'maturity_date',
    'currency_code',
    'status',
    'last_contribution_date',
    'notes',
])]
class SavingsItem extends Model
{
    /** @use HasFactory<SavingsItemFactory> */
    use HasFactory;

    use IsSchedulableItem;

    /**
     * @var list<string>
     */
    public const array SORTABLE_COLUMNS = ['name', 'type', 'amount', 'saved'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The amount saved so far: installment amount x installments made,
     * clamped at the target so an over-recorded contribution count can't
     * exceed the goal.
     */
    public function amountSaved(): float
    {
        $saved = (float) $this->installment_amount * $this->installments_made;

        return min((float) $this->target_amount, $saved);
    }

    public function remainingToTarget(): float
    {
        return max(0.0, (float) $this->target_amount - $this->amountSaved());
    }

    /**
     * A proportional estimate of profit earned so far - target profit
     * isn't independently tracked, so this scales it by how much of the
     * target amount has been saved.
     */
    public function profitEarned(): float
    {
        if ($this->target_profit === null || (float) $this->target_amount <= 0.0) {
            return 0.0;
        }

        return (float) $this->target_profit * ($this->amountSaved() / (float) $this->target_amount);
    }

    /**
     * Whether a contribution has already been recorded for whichever
     * period is currently active, so the "Record Contribution" action can
     * be disabled rather than allowing an accidental double count. Mirrors
     * DebtItem::hasPaidCurrentPeriod() exactly, including comparing from
     * the contribution date itself rather than the day after (see that
     * method's docblock for why the offset is wrong).
     */
    public function hasContributedCurrentPeriod(): bool
    {
        if ($this->last_contribution_date === null) {
            return false;
        }

        if (! $this->schedule?->recurrence) {
            return true;
        }

        $fromLastContribution = $this->nextPaymentDate($this->last_contribution_date);
        $fromToday = $this->nextPaymentDate(CarbonImmutable::today());

        return $fromLastContribution !== null && $fromToday !== null && $fromLastContribution->equalTo($fromToday);
    }

    /**
     * @param  Builder<SavingsItem>  $query
     */
    #[Scope]
    protected function sortable(Builder $query, string $column, SortDirection $direction): void
    {
        if (! in_array($column, self::SORTABLE_COLUMNS, true)) {
            $column = 'name';
        }

        if ($column === 'amount') {
            $query->orderBy('installment_amount', $direction->value);

            return;
        }

        if ($column === 'saved') {
            $query->orderByRaw('LEAST(target_amount, installment_amount * installments_made) '.$direction->value);

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
            'target_amount' => 'decimal:2',
            'installment_amount' => 'decimal:2',
            'installments_made' => 'integer',
            'target_profit' => 'decimal:2',
            'maturity_date' => 'date',
            'status' => SavingsItemStatus::class,
            'type' => SavingsItemType::class,
            'last_contribution_date' => 'date',
        ];
    }
}
