<?php

namespace App\Models;

use App\Enums\ItemStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $budget_month_id
 * @property int $user_id
 * @property int $category_id
 * @property string $name
 * @property string $amount
 * @property string $currency_code
 * @property ItemStatus $status
 * @property bool $is_recurring
 * @property int|null $recurring_group_id
 * @property int|null $recurrence_months_remaining
 * @property int|null $reminder_day
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'category_id',
    'name',
    'amount',
    'currency_code',
    'status',
    'is_recurring',
    'recurring_group_id',
    'recurrence_months_remaining',
    'reminder_day',
    'notes',
])]
class NeedsItem extends Model
{
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => ItemStatus::class,
            'is_recurring' => 'boolean',
        ];
    }
}
