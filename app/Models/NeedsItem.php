<?php

namespace App\Models;

use App\Enums\NeedsItemStatus;
use App\Enums\SortDirection;
use Carbon\CarbonImmutable;
use Database\Factories\NeedsItemFactory;
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
 * @property int $category_id
 * @property int|null $schedule_id
 * @property string $name
 * @property string $amount
 * @property string $currency_code
 * @property NeedsItemStatus $status
 * @property CarbonImmutable|null $date_due
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'category_id',
    'schedule_id',
    'name',
    'amount',
    'currency_code',
    'status',
    'date_due',
    'notes',
])]
class NeedsItem extends Model
{
    /** @use HasFactory<NeedsItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    public const array SORTABLE_COLUMNS = ['name', 'category', 'amount'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Schedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    /**
     * The next upcoming occurrence of this item's due date, from today.
     * Recurring items roll forward into next month once this month's
     * due day has passed; one-time items with a past date_due (or no
     * date_due at all) are considered unscheduled.
     */
    public function nextPaymentDate(?CarbonImmutable $today = null): ?CarbonImmutable
    {
        $today ??= CarbonImmutable::today();

        if ($this->schedule?->is_active && $this->schedule->due_day) {
            $dueDay = $this->schedule->due_day;

            $thisMonth = CarbonImmutable::create($today->year, $today->month, 1)
                ->day(min($dueDay, $today->daysInMonth));

            if (! $thisMonth->lt($today)) {
                return $thisMonth;
            }

            $nextMonth = $today->addMonthNoOverflow();

            return CarbonImmutable::create($nextMonth->year, $nextMonth->month, 1)
                ->day(min($dueDay, $nextMonth->daysInMonth));
        }

        if ($this->date_due && ! $this->date_due->lt($today)) {
            return $this->date_due;
        }

        return null;
    }

    /**
     * @param  Builder<NeedsItem>  $query
     */
    #[Scope]
    protected function sortable(Builder $query, string $column, SortDirection $direction): void
    {
        if (! in_array($column, self::SORTABLE_COLUMNS, true)) {
            $column = 'name';
        }

        if ($column === 'category') {
            $query->orderBy(
                Category::select('name')->whereColumn('categories.id', 'needs_items.category_id'),
                $direction->value,
            );

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
            'amount' => 'decimal:2',
            'status' => NeedsItemStatus::class,
            'date_due' => 'date',
        ];
    }
}
