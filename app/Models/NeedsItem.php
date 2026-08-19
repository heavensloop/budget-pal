<?php

namespace App\Models;

use App\Enums\MonthlyRecurrence;
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
     * The next upcoming occurrence of this item's schedule, from today.
     * A one-time schedule (recurrence null) is its own due date, and is
     * considered unscheduled once it's passed. Recurring schedules roll
     * forward to the next occurrence on/after today, never before their
     * own start_date, and return null once past their end_date.
     */
    public function nextPaymentDate(?CarbonImmutable $today = null): ?CarbonImmutable
    {
        $today ??= CarbonImmutable::today();
        $schedule = $this->schedule;

        if (! $schedule?->is_active || ! $schedule->start_date) {
            return null;
        }

        if ($schedule->recurrence === null) {
            return $schedule->start_date->lt($today) ? null : $schedule->start_date;
        }

        $dueDate = match ($schedule->recurrence) {
            MonthlyRecurrence::Monthly => $this->nextMonthlyOccurrence($schedule->start_date, $today),
            MonthlyRecurrence::EveryNMonths => $this->nextEveryNMonthsOccurrence($schedule->start_date, $today, $schedule->interval_months),
            MonthlyRecurrence::SpecificMonths => $this->nextSpecificMonthsOccurrence($schedule->start_date, $today, $schedule->months ?? []),
        };

        if ($schedule->end_date && $dueDate->gt($schedule->end_date)) {
            return null;
        }

        return $dueDate;
    }

    private function nextMonthlyOccurrence(CarbonImmutable $startDate, CarbonImmutable $today): CarbonImmutable
    {
        if ($startDate->gt($today)) {
            return $startDate;
        }

        $day = $startDate->day;
        $thisMonth = CarbonImmutable::create($today->year, $today->month, 1)->day(min($day, $today->daysInMonth));

        if (! $thisMonth->lt($today)) {
            return $thisMonth;
        }

        $nextMonth = $today->addMonthNoOverflow();

        return CarbonImmutable::create($nextMonth->year, $nextMonth->month, 1)->day(min($day, $nextMonth->daysInMonth));
    }

    private function nextEveryNMonthsOccurrence(CarbonImmutable $startDate, CarbonImmutable $today, int $intervalMonths): CarbonImmutable
    {
        if ($startDate->gt($today)) {
            return $startDate;
        }

        $monthsElapsed = ($today->year - $startDate->year) * 12 + ($today->month - $startDate->month);
        $periodsElapsed = intdiv($monthsElapsed, $intervalMonths);

        $candidate = $this->addClampedMonths($startDate, $periodsElapsed * $intervalMonths);

        return $candidate->lt($today)
            ? $this->addClampedMonths($startDate, ($periodsElapsed + 1) * $intervalMonths)
            : $candidate;
    }

    /**
     * @param  list<int>  $months  1 (January) through 12 (December)
     */
    private function nextSpecificMonthsOccurrence(CarbonImmutable $startDate, CarbonImmutable $today, array $months): CarbonImmutable
    {
        if ($startDate->gt($today)) {
            return $startDate;
        }

        sort($months);
        $day = $startDate->day;

        foreach ([$today->year, $today->year + 1] as $year) {
            foreach ($months as $month) {
                $daysInMonth = CarbonImmutable::create($year, $month, 1)->daysInMonth;
                $candidate = CarbonImmutable::create($year, $month, 1)->day(min($day, $daysInMonth));

                if (! $candidate->lt($today)) {
                    return $candidate;
                }
            }
        }

        throw new \LogicException('Unreachable: scanned two full years without finding an occurrence.');
    }

    private function addClampedMonths(CarbonImmutable $date, int $months): CarbonImmutable
    {
        $target = CarbonImmutable::create($date->year, $date->month, 1)->addMonthsNoOverflow($months);

        return $target->day(min($date->day, $target->daysInMonth));
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
        ];
    }
}
