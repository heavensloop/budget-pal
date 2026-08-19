<?php

namespace App\Actions\Budget;

use App\Enums\DebtItemStatus;
use App\Models\DebtItem;
use App\Models\Schedule;
use App\Models\User;

class CreateDebtItem
{
    /**
     * @param  array<string, mixed>  $data  validated StoreDebtItemRequest input
     */
    public function __invoke(User $user, array $data): DebtItem
    {
        $schedule = null;

        if (! empty($data['schedule'])) {
            $schedule = Schedule::create([
                'is_active' => true,
                'recurrence' => $data['schedule']['recurrence'] ?? null,
                'start_date' => $data['schedule']['start_date'],
                'end_date' => $data['schedule']['end_date'] ?? null,
                'reminder_days_before' => $data['schedule']['reminder_days_before'] ?? null,
                'interval_months' => $data['schedule']['interval_months'] ?? null,
                'months' => isset($data['schedule']['months']) ? array_map(intval(...), $data['schedule']['months']) : null,
            ]);
        }

        return DebtItem::create([
            'user_id' => $user->id,
            'category' => $data['category'],
            'schedule_id' => $schedule?->id,
            'name' => $data['name'],
            'principal' => $data['principal'],
            'balance' => $data['balance'] ?? $data['principal'],
            'amount' => $data['amount'],
            'currency_code' => 'NGN',
            'status' => DebtItemStatus::Pending,
            'notes' => $data['notes'] ?? null,
        ]);
    }
}
