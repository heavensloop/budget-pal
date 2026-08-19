<?php

namespace App\Actions\Budget;

use App\Enums\NeedsItemStatus;
use App\Enums\RecurrenceFrequency;
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

        if (! empty($data['is_recurring'])) {
            $schedule = Schedule::create([
                'is_active' => true,
                'recurrence' => RecurrenceFrequency::Monthly,
                'due_day' => $data['due_day'] ?? null,
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
            'date_due' => $schedule ? null : ($data['date_due'] ?? null),
            'notes' => $data['notes'] ?? null,
        ]);
    }
}
