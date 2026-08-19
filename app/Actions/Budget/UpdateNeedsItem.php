<?php

namespace App\Actions\Budget;

use App\Models\NeedsItem;
use App\Models\Schedule;

class UpdateNeedsItem
{
    /**
     * @param  array<string, mixed>  $data  validated UpdateNeedsItemRequest input
     */
    public function __invoke(NeedsItem $item, array $data): NeedsItem
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
                ]);
            } else {
                $item->schedule_id = Schedule::create([
                    'is_active' => true,
                    'recurrence' => $scheduleData['recurrence'] ?? null,
                    'start_date' => $scheduleData['start_date'],
                    'end_date' => $scheduleData['end_date'] ?? null,
                    'reminder_days_before' => $scheduleData['reminder_days_before'] ?? null,
                ])->id;
            }
        } elseif ($item->schedule) {
            $orphanedSchedule = $item->schedule;
            $item->schedule_id = null;
        }

        $item->update([
            'schedule_id' => $item->schedule_id,
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'amount' => $data['amount'],
            'notes' => $data['notes'] ?? null,
        ]);

        $orphanedSchedule?->delete();

        return $item;
    }
}
