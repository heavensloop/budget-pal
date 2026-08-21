<?php

namespace App\Actions\Budget;

use App\Enums\WantItemStatus;
use App\Models\WantItem;
use Illuminate\Support\Facades\DB;

class ReorderWantItem
{
    /**
     * Swap this item's position with its neighbor among the user's other
     * Planned items (Purchased/Archived items are skipped entirely, since
     * once something is off the wishlist its priority no longer matters).
     * A no-op if there's no neighbor in that direction.
     */
    public function __invoke(WantItem $item, string $direction): WantItem
    {
        if ($item->status !== WantItemStatus::PLANNED) {
            return $item;
        }

        $neighborQuery = WantItem::query()
            ->where('user_id', $item->user_id)
            ->where('status', WantItemStatus::PLANNED)
            ->where('id', '!=', $item->id);

        $neighbor = $direction === 'up'
            ? $neighborQuery->where('position', '<', $item->position)->orderByDesc('position')->first()
            : $neighborQuery->where('position', '>', $item->position)->orderBy('position')->first();

        if (! $neighbor) {
            return $item;
        }

        DB::transaction(function () use ($item, $neighbor) {
            $itemPosition = $item->position;
            $item->update(['position' => $neighbor->position]);
            $neighbor->update(['position' => $itemPosition]);
        });

        return $item->fresh();
    }
}
