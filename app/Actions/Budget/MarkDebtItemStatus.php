<?php

namespace App\Actions\Budget;

use App\Enums\DebtItemStatus;
use App\Models\DebtItem;

class MarkDebtItemStatus
{
    public function __invoke(DebtItem $item, DebtItemStatus $status): DebtItem
    {
        $item->update(['status' => $status]);

        return $item;
    }
}
