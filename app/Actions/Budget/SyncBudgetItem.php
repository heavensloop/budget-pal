<?php

namespace App\Actions\Budget;

use App\Enums\BudgetItemType;
use App\Models\BudgetItem;
use App\Models\NeedsItem;
use Carbon\CarbonImmutable;

class SyncBudgetItem
{
    /**
     * Project a NeedsItem onto its budget_items row, creating or updating
     * it to match. The source row is always authoritative; this never
     * writes anything back to it.
     */
    public function __invoke(NeedsItem $item): BudgetItem
    {
        $dateDue = $this->resolveDateDue($item);

        return BudgetItem::updateOrCreate(
            ['source_type' => NeedsItem::class, 'source_id' => $item->id],
            [
                'budget_month_id' => $item->budget_month_id,
                'type' => BudgetItemType::Needs,
                'name' => $item->name,
                'amount' => $item->amount,
                'currency_code' => $item->currency_code,
                'status' => $item->status,
                'date_due' => $dateDue,
                'message' => $this->resolveMessage($item, $dateDue),
            ],
        );
    }

    private function resolveDateDue(NeedsItem $item): ?CarbonImmutable
    {
        if ($item->date_due) {
            return $item->date_due;
        }

        $dueDay = $item->schedule?->due_day;

        if (! $dueDay) {
            return null;
        }

        $budgetMonth = $item->budgetMonth;
        $firstOfMonth = CarbonImmutable::create($budgetMonth->year, $budgetMonth->month, 1);

        return $firstOfMonth->day(min($dueDay, $firstOfMonth->daysInMonth));
    }

    private function resolveMessage(NeedsItem $item, ?CarbonImmutable $dateDue): ?string
    {
        if (! $dateDue) {
            return null;
        }

        if ($item->schedule?->due_day) {
            return 'Due on the '.$dateDue->format('jS').' of the month';
        }

        return 'Due '.$dateDue->format('M j, Y');
    }
}
