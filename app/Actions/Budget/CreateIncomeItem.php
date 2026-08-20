<?php

namespace App\Actions\Budget;

use App\Enums\IncomeItemStatus;
use App\Models\IncomeItem;
use App\Models\Schedule;
use App\Models\User;

class CreateIncomeItem
{
    /**
     * @param  array<string, mixed>  $data  validated StoreIncomeItemRequest input
     */
    public function __invoke(User $user, array $data): IncomeItem
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

        return IncomeItem::create([
            'user_id' => $user->id,
            'category' => $data['category'],
            'schedule_id' => $schedule?->id,
            'name' => $data['name'],
            'amount' => $data['amount'],
            'currency_code' => 'NGN',
            'status' => IncomeItemStatus::Pending,
            'notes' => $data['notes'] ?? null,
        ]);
    }
}
