<?php

namespace App\Actions\Budget;

use App\Enums\DebtItemStatus;
use App\Models\DebtItem;
use Carbon\CarbonImmutable;

class RecordDebtPayment
{
    public function __invoke(DebtItem $item): DebtItem
    {
        $newBalance = max(0.0, round((float) $item->balance - (float) $item->amount, 2));
        $item->balance = number_format($newBalance, 2, '.', '');
        $item->last_payment_date = CarbonImmutable::today();

        if ((float) $item->balance <= 0.0) {
            $item->status = DebtItemStatus::Archived;
        }

        $item->save();

        return $item;
    }
}
