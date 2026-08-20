<?php

namespace App\Actions\Budget;

use App\Enums\DebtItemStatus;
use App\Models\DebtItem;
use Carbon\CarbonImmutable;

class RecordDebtPayment
{
    public function __invoke(DebtItem $item): DebtItem
    {
        $item->payments_made = min($item->tenure_months, $item->payments_made + 1);
        $item->last_payment_date = CarbonImmutable::today();

        if ($item->payments_made >= $item->tenure_months) {
            $item->status = DebtItemStatus::Archived;
        }

        $item->save();

        return $item;
    }
}
