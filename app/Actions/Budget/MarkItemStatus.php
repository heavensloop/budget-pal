<?php

namespace App\Actions\Budget;

use App\Enums\NeedsItemStatus;
use App\Models\NeedsItem;

class MarkItemStatus
{
    public function __invoke(NeedsItem $item, NeedsItemStatus $status): NeedsItem
    {
        $item->update(['status' => $status]);

        return $item;
    }
}
