<?php

namespace App\Models;

use App\Enums\MonthlyRecurrence;
use Carbon\CarbonImmutable;
use Database\Factories\ScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property bool $is_active
 * @property MonthlyRecurrence|null $recurrence
 * @property CarbonImmutable|null $start_date
 * @property CarbonImmutable|null $end_date
 * @property int|null $reminder_days_before
 * @property int|null $interval_months
 * @property list<int>|null $months
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['is_active', 'recurrence', 'start_date', 'end_date', 'reminder_days_before', 'interval_months', 'months'])]
class Schedule extends Model
{
    /** @use HasFactory<ScheduleFactory> */
    use HasFactory;

    /**
     * @return HasMany<NeedsItem, $this>
     */
    public function needsItems(): HasMany
    {
        return $this->hasMany(NeedsItem::class);
    }

    /**
     * @return array{recurrence: string|null, startDate: string|null, endDate: string|null, reminderDaysBefore: int|null, intervalMonths: int|null, months: list<int>|null}
     */
    public function toFrontendArray(): array
    {
        return [
            'recurrence' => $this->recurrence?->value,
            'startDate' => $this->start_date?->toDateString(),
            'endDate' => $this->end_date?->toDateString(),
            'reminderDaysBefore' => $this->reminder_days_before,
            'intervalMonths' => $this->interval_months,
            'months' => $this->months,
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'recurrence' => MonthlyRecurrence::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'reminder_days_before' => 'integer',
            'interval_months' => 'integer',
            'months' => 'array',
        ];
    }
}
