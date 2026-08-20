<?php

namespace App\Actions\Budget;

use App\Models\DebtItem;
use App\Models\Schedule;

class UpdateDebtItem
{
    /**
     * @param  array<string, mixed>  $data  validated UpdateDebtItemRequest input
     */
    public function __invoke(DebtItem $item, array $data): DebtItem
    {
        $scheduleData = $data['schedule'] ?? null;
        $orphanedSchedule = null;

        if ($scheduleData) {
            if ($item->schedule) {
                $item->schedule->update([
                    'recurrence' => $scheduleData['recurrence'] ?? null,
                    'start_date' => $scheduleData['start_date'],
                    'end_date' => $scheduleData['end_date'] ?? null,
                    'reminder_days_before' => $scheduleData['reminder_days_before'] ?? null,
                    'interval_months' => $scheduleData['interval_months'] ?? null,
                    'months' => isset($scheduleData['months']) ? array_map(intval(...), $scheduleData['months']) : null,
                ]);
            } else {
                $item->schedule_id = Schedule::create([
                    'is_active' => true,
                    'recurrence' => $scheduleData['recurrence'] ?? null,
                    'start_date' => $scheduleData['start_date'],
                    'end_date' => $scheduleData['end_date'] ?? null,
                    'reminder_days_before' => $scheduleData['reminder_days_before'] ?? null,
                    'interval_months' => $scheduleData['interval_months'] ?? null,
                    'months' => isset($scheduleData['months']) ? array_map(intval(...), $scheduleData['months']) : null,
                ])->id;
            }
        } elseif ($item->schedule) {
            $orphanedSchedule = $item->schedule;
            $item->schedule_id = null;
        }

        $item->update([
            'schedule_id' => $item->schedule_id,
            'category' => $data['category'],
            'name' => $data['name'],
            'amount_borrowed' => $data['amount_borrowed'],
            'total_repayment_amount' => $data['total_repayment_amount'],
            'monthly_repayment_amount' => $data['monthly_repayment_amount'],
            'tenure_months' => $data['tenure_months'],
            'payments_made' => $data['payments_made'] ?? $item->payments_made,
            'notes' => $data['notes'] ?? null,
        ]);

        $orphanedSchedule?->delete();

        return $item;
    }
}
