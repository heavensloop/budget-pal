<?php

namespace App\Actions\Budget;

use App\Models\SavingsItem;
use App\Models\Schedule;

class UpdateSavingsItem
{
    /**
     * @param  array<string, mixed>  $data  validated UpdateSavingsItemRequest input
     */
    public function __invoke(SavingsItem $item, array $data): SavingsItem
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
            'type' => $data['type'],
            'name' => $data['name'],
            'target_amount' => $data['target_amount'],
            'installment_amount' => $data['installment_amount'],
            'target_profit' => $data['target_profit'] ?? null,
            'maturity_date' => $data['maturity_date'] ?? null,
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
        ]);

        $orphanedSchedule?->delete();

        return $item;
    }
}
