<?php

namespace App\Actions\Budget;

use App\Enums\RecurrenceFrequency;
use App\Models\NeedsItem;
use App\Models\Schedule;

class UpdateNeedsItem
{
    /**
     * @param  array<string, mixed>  $data  validated UpdateNeedsItemRequest input
     */
    public function __invoke(NeedsItem $item, array $data): NeedsItem
    {
        $isRecurring = ! empty($data['is_recurring']);

        if ($isRecurring && ! $item->schedule_id) {
            $schedule = Schedule::create([
                'is_active' => true,
                'recurrence' => RecurrenceFrequency::Monthly,
                'due_day' => $data['due_day'] ?? null,
            ]);
            $item->schedule_id = $schedule->id;
        } elseif ($item->schedule) {
            $item->schedule->update([
                'is_active' => $isRecurring,
                'due_day' => $data['due_day'] ?? $item->schedule->due_day,
            ]);
        }

        $item->update([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'amount' => $data['amount'],
            'date_due' => $isRecurring ? null : ($data['date_due'] ?? null),
            'notes' => $data['notes'] ?? null,
        ]);

        return $item;
    }
}
