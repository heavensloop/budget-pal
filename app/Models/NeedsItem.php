<?php

namespace App\Models;

use App\Enums\ItemStatus;
use Carbon\CarbonImmutable;
use Database\Factories\NeedsItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $budget_month_id
 * @property int $user_id
 * @property int $category_id
 * @property int|null $schedule_id
 * @property string $name
 * @property string $amount
 * @property string $currency_code
 * @property ItemStatus $status
 * @property CarbonImmutable|null $date_due
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'budget_month_id',
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
     * @return BelongsTo<BudgetMonth, $this>
     */
    public function budgetMonth(): BelongsTo
    {
        return $this->belongsTo(BudgetMonth::class);
    }

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
     * @return MorphOne<BudgetItem, $this>
     */
    public function budgetItem(): MorphOne
    {
        return $this->morphOne(BudgetItem::class, 'source');
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
            'status' => ItemStatus::class,
            'date_due' => 'date',
        ];
    }
}
