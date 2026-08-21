<?php

namespace App\Actions\Budget;

use App\Enums\WantItemStatus;
use App\Models\WantItem;
use Carbon\CarbonImmutable;

class MarkWantItemStatus
{
    public function __invoke(WantItem $item, WantItemStatus $status): WantItem
    {
        $item->status = $status;

        if ($status === WantItemStatus::PURCHASED) {
            $item->purchased_at ??= CarbonImmutable::today();
        } elseif ($status === WantItemStatus::PLANNED) {
            $item->purchased_at = null;
        }

        $item->save();

        return $item;
    }
}
