<?php

namespace App\Actions\Budget;

use App\Enums\SavingsItemStatus;
use App\Models\SavingsItem;

class MarkSavingsItemStatus
{
    public function __invoke(SavingsItem $item, SavingsItemStatus $status): SavingsItem
    {
        $item->update(['status' => $status]);

        return $item;
    }
}
