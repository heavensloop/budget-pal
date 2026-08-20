<?php

namespace App\Actions\Budget;

use App\Models\SavingsItem;
use App\Models\Schedule;
use App\Models\User;

class CreateSavingsItem
{
    /**
     * @param  array<string, mixed>  $data  validated StoreSavingsItemRequest input
     */
    public function __invoke(User $user, array $data): SavingsItem
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

        return SavingsItem::create([
            'user_id' => $user->id,
            'type' => $data['type'],
            'schedule_id' => $schedule?->id,
            'name' => $data['name'],
            'target_amount' => $data['target_amount'],
            'installment_amount' => $data['installment_amount'],
            'installments_made' => 0,
            'target_profit' => $data['target_profit'] ?? null,
            'maturity_date' => $data['maturity_date'] ?? null,
            'currency_code' => 'NGN',
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
        ]);
    }
}
