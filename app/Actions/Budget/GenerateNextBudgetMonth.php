<?php

namespace App\Actions\Budget;

use App\Models\BudgetMonth;
use App\Models\User;

class GenerateNextBudgetMonth
{
    /**
     * Get or create the budget month for (year, month). Idempotent.
     */
    public function __invoke(User $user, int $year, int $month): BudgetMonth
    {
        return BudgetMonth::firstOrCreate([
            'user_id' => $user->id,
            'year' => $year,
            'month' => $month,
        ]);
    }
}
