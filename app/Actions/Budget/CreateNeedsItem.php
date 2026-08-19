<?php

namespace App\Actions\Budget;

use App\Enums\NeedsItemStatus;
use App\Models\NeedsItem;
use App\Models\Schedule;
use App\Models\User;

class CreateNeedsItem
{
    /**
     * @param  array<string, mixed>  $data  validated StoreNeedsItemRequest input
     */
    public function __invoke(User $user, array $data): NeedsItem
    {
        $schedule = null;

        if (! empty($data['schedule'])) {
            $schedule = Schedule::create([
                'is_active' => true,
                'recurrence' => $data['schedule']['recurrence'] ?? null,
                'start_date' => $data['schedule']['start_date'],
                'end_date' => $data['schedule']['end_date'] ?? null,
                'reminder_days_before' => $data['schedule']['reminder_days_before'] ?? null,
            ]);
        }

        return NeedsItem::create([
            'user_id' => $user->id,
            'category_id' => $data['category_id'],
            'schedule_id' => $schedule?->id,
            'name' => $data['name'],
            'amount' => $data['amount'],
            'currency_code' => 'NGN',
            'status' => NeedsItemStatus::Pending,
            'notes' => $data['notes'] ?? null,
        ]);
    }
}
