<?php

namespace App\Actions\Budget;

use App\Enums\WantItemStatus;
use App\Models\User;
use App\Models\WantItem;

class CreateWantItem
{
    /**
     * @param  array<string, mixed>  $data  validated StoreWantItemRequest input
     */
    public function __invoke(User $user, array $data): WantItem
    {
        $lastPosition = WantItem::query()
            ->where('user_id', $user->id)
            ->max('position');

        return WantItem::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'category' => $data['category'],
            'amount' => $data['amount'],
            'currency_code' => 'NGN',
            'status' => WantItemStatus::PLANNED,
            'position' => ((int) $lastPosition) + 1,
            'notes' => $data['notes'] ?? null,
        ]);
    }
}
