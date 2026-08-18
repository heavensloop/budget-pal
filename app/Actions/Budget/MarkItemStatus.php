<?php

namespace App\Actions\Budget;

use App\Enums\ItemStatus;
use App\Models\NeedsItem;

class MarkItemStatus
{
    public function __construct(private readonly SyncBudgetItem $syncBudgetItem) {}

    public function __invoke(NeedsItem $item, ItemStatus $status): NeedsItem
    {
        $item->update(['status' => $status]);

        ($this->syncBudgetItem)($item);

        return $item;
    }
}
