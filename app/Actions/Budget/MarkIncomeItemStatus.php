<?php

namespace App\Actions\Budget;

use App\Enums\IncomeItemStatus;
use App\Models\IncomeItem;

class MarkIncomeItemStatus
{
    public function __invoke(IncomeItem $item, IncomeItemStatus $status): IncomeItem
    {
        $item->update(['status' => $status]);

        return $item;
    }
}
