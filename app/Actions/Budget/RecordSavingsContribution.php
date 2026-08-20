<?php

namespace App\Actions\Budget;

use App\Enums\SavingsItemStatus;
use App\Models\SavingsItem;
use Carbon\CarbonImmutable;

class RecordSavingsContribution
{
    public function __invoke(SavingsItem $item): SavingsItem
    {
        $item->installments_made++;
        $item->last_contribution_date = CarbonImmutable::today();

        if ($item->amountSaved() >= (float) $item->target_amount) {
            $item->status = SavingsItemStatus::COMPLETED;
        }

        $item->save();

        return $item;
    }
}
