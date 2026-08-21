<?php

namespace App\Actions\Budget;

use App\Models\WantItem;

class UpdateWantItem
{
    /**
     * @param  array<string, mixed>  $data  validated UpdateWantItemRequest input
     */
    public function __invoke(WantItem $item, array $data): WantItem
    {
        $item->update([
            'name' => $data['name'],
            'category' => $data['category'],
            'amount' => $data['amount'],
            'notes' => $data['notes'] ?? null,
        ]);

        return $item;
    }
}
