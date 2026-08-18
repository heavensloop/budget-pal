<?php

namespace App\Actions\Budget;

use App\Enums\ItemStatus;
use App\Models\BudgetMonth;
use App\Models\User;
use Illuminate\Support\Carbon;

class GenerateNextBudgetMonth
{
    public function __construct(private readonly SyncBudgetItem $syncBudgetItem) {}

    /**
     * Get or create the budget month for (year, month), carrying forward
     * any still-active recurring Needs items from the immediately
     * preceding calendar month. Carrying forward always copies the
     * amount from the prior instance and resets status to pending.
     * Idempotent: re-running never duplicates an already-carried item.
     */
    public function __invoke(User $user, int $year, int $month): BudgetMonth
    {
        $budgetMonth = BudgetMonth::firstOrCreate([
            'user_id' => $user->id,
            'year' => $year,
            'month' => $month,
        ]);

        $previousDate = Carbon::create($year, $month, 1)->subMonthNoOverflow();

        $previousMonth = BudgetMonth::where('user_id', $user->id)
            ->where('year', $previousDate->year)
            ->where('month', $previousDate->month)
            ->first();

        if (! $previousMonth) {
            return $budgetMonth;
        }

        $recurringItems = $previousMonth->needsItems()
            ->whereNotNull('schedule_id')
            ->whereHas('schedule', fn ($query) => $query->where('is_active', true))
            ->get();

        foreach ($recurringItems as $sourceItem) {
            $alreadyCarried = $budgetMonth->needsItems()
                ->where('schedule_id', $sourceItem->schedule_id)
                ->exists();

            if ($alreadyCarried) {
                continue;
            }

            $newItem = $budgetMonth->needsItems()->create([
                'user_id' => $user->id,
                'category_id' => $sourceItem->category_id,
                'schedule_id' => $sourceItem->schedule_id,
                'name' => $sourceItem->name,
                'amount' => $sourceItem->amount,
                'currency_code' => $sourceItem->currency_code,
                'status' => ItemStatus::Pending,
            ]);

            ($this->syncBudgetItem)($newItem);
        }

        return $budgetMonth;
    }
}
