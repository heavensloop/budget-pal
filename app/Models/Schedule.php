<?php

namespace App\Models;

use App\Enums\RecurrenceFrequency;
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
 * @property RecurrenceFrequency|null $recurrence
 * @property CarbonImmutable|null $start_date
 * @property CarbonImmutable|null $end_date
 * @property int|null $due_day
 * @property int|null $reminder_days_before
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['is_active', 'recurrence', 'start_date', 'end_date', 'due_day', 'reminder_days_before'])]
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'recurrence' => RecurrenceFrequency::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'due_day' => 'integer',
            'reminder_days_before' => 'integer',
        ];
    }
}
