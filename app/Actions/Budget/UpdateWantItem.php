<?php

namespace App\Actions\Budget;

use App\Models\Schedule;
use App\Models\WantItem;

class UpdateWantItem
{
    /**
     * @param  array<string, mixed>  $data  validated UpdateWantItemRequest input
     */
    public function __invoke(WantItem $item, array $data): WantItem
    {
        $scheduleData = $data['schedule'] ?? null;
        $orphanedSchedule = null;

        if ($scheduleData) {
            if ($item->schedule) {
                $item->schedule->update([
                    'start_date' => $scheduleData['start_date'],
                    'reminder_days_before' => $scheduleData['reminder_days_before'] ?? null,
                ]);
            } else {
                $item->schedule_id = Schedule::create([
                    'is_active' => true,
                    'recurrence' => null,
                    'start_date' => $scheduleData['start_date'],
                    'reminder_days_before' => $scheduleData['reminder_days_before'] ?? null,
                ])->id;
            }
        } elseif ($item->schedule) {
            $orphanedSchedule = $item->schedule;
            $item->schedule_id = null;
        }

        $item->update([
            'schedule_id' => $item->schedule_id,
            'name' => $data['name'],
            'category' => $data['category'],
            'amount' => $data['amount'],
            'notes' => $data['notes'] ?? null,
        ]);

        $orphanedSchedule?->delete();

        return $item;
    }
}
