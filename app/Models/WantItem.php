<?php

namespace App\Models;

use App\Enums\SortDirection;
use App\Enums\WantCategory;
use App\Enums\WantItemStatus;
use Carbon\CarbonImmutable;
use Database\Factories\WantItemFactory;
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
 * @property int|null $schedule_id
 * @property string $name
 * @property WantCategory $category
 * @property string $amount
 * @property string $currency_code
 * @property WantItemStatus $status
 * @property int $position
 * @property CarbonImmutable|null $purchased_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'schedule_id',
    'name',
    'category',
    'amount',
    'currency_code',
    'status',
    'position',
    'purchased_at',
    'notes',
])]
class WantItem extends Model
{
    /** @use HasFactory<WantItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    public const array SORTABLE_COLUMNS = ['name', 'category', 'amount', 'position'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The target purchase month, if set. Wants are one-off purchases, not
     * recurring bills, so unlike IsSchedulableItem's schedule() this never
     * needs occurrence-computation logic - the schedule's own start_date
     * already is the target date.
     *
     * @return BelongsTo<Schedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    /**
     * @param  Builder<WantItem>  $query
     */
    #[Scope]
    protected function sortable(Builder $query, string $column, SortDirection $direction): void
    {
        if (! in_array($column, self::SORTABLE_COLUMNS, true)) {
            $column = 'position';
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
            'category' => WantCategory::class,
            'amount' => 'decimal:2',
            'status' => WantItemStatus::class,
            'position' => 'integer',
            'purchased_at' => 'date',
        ];
    }
}
