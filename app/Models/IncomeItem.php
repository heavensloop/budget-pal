<?php

namespace App\Models;

use App\Concerns\IsSchedulableItem;
use App\Enums\IncomeCategory;
use App\Enums\IncomeItemStatus;
use App\Enums\SortDirection;
use Database\Factories\IncomeItemFactory;
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
 * @property IncomeCategory $category
 * @property int|null $schedule_id
 * @property string $name
 * @property string $amount
 * @property string $currency_code
 * @property IncomeItemStatus $status
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'category',
    'schedule_id',
    'name',
    'amount',
    'currency_code',
    'status',
    'notes',
])]
class IncomeItem extends Model
{
    /** @use HasFactory<IncomeItemFactory> */
    use HasFactory;

    use IsSchedulableItem;

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
     * @param  Builder<IncomeItem>  $query
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
            'amount' => 'decimal:2',
            'status' => IncomeItemStatus::class,
            'category' => IncomeCategory::class,
        ];
    }
}
