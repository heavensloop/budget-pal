<?php

namespace App\Models;

use App\Enums\BudgetItemType;
use App\Enums\ItemStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $budget_month_id
 * @property string $source_type
 * @property int $source_id
 * @property BudgetItemType $type
 * @property string $name
 * @property string $amount
 * @property string $currency_code
 * @property ItemStatus $status
 * @property CarbonImmutable|null $date_due
 * @property string|null $message
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['budget_month_id', 'source_type', 'source_id', 'type', 'name', 'amount', 'currency_code', 'status', 'date_due', 'message'])]
class BudgetItem extends Model
{
    /**
     * @return BelongsTo<BudgetMonth, $this>
     */
    public function budgetMonth(): BelongsTo
    {
        return $this->belongsTo(BudgetMonth::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => BudgetItemType::class,
            'amount' => 'decimal:2',
            'status' => ItemStatus::class,
            'date_due' => 'date',
        ];
    }
}
