<?php

namespace App\Concerns;

use App\Enums\MonthlyRecurrence;
use App\Models\Schedule;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int|null $schedule_id
 */
trait IsSchedulableItem
{
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
            MonthlyRecurrence::Quarterly => $this->nextEveryNMonthsOccurrence($schedule->start_date, $today, 3),
            MonthlyRecurrence::Yearly => $this->nextEveryNMonthsOccurrence($schedule->start_date, $today, 12),
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
        // Unlike the other recurrences, the month itself is chosen by
        // $months rather than $startDate, so there's no "hasn't started
        // yet" short-circuit here - $startDate only ever supplies the day
        // of the month, and the search below already finds the earliest
        // matching occurrence on/after today regardless of where
        // $startDate itself falls.
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
}
