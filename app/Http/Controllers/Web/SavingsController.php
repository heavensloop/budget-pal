<?php

namespace App\Http\Controllers\Web;

use App\Actions\Budget\CreateSavingsItem;
use App\Actions\Budget\MarkSavingsItemStatus;
use App\Actions\Budget\RecordSavingsContribution;
use App\Actions\Budget\UpdateSavingsItem;
use App\Enums\MonthlyRecurrence;
use App\Enums\SavingsItemStatus;
use App\Enums\SavingsItemType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Savings\ShowSavingsRequest;
use App\Http\Requests\Savings\StoreSavingsItemRequest;
use App\Http\Requests\Savings\UpdateSavingsItemRequest;
use App\Models\SavingsItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SavingsController extends Controller
{
    public function index(ShowSavingsRequest $request): Response
    {
        $sort = $request->getSortField('name');
        $direction = $request->getSortDirection();
        $showArchived = $request->boolean('show_archived');

        $query = SavingsItem::query()
            ->where('user_id', $request->user()->id)
            ->with(['schedule'])
            ->sortable($sort, $direction);

        if (! $showArchived) {
            $query->where('status', '!=', SavingsItemStatus::ARCHIVED);
        }

        $items = $query->get()->map($this->mapItem(...));

        return Inertia::render('Savings/Index', [
            'typeOptions' => SavingsItemType::options(),
            'statusOptions' => SavingsItemStatus::options(),
            'items' => $items,
            'sort' => $sort,
            'direction' => $direction->value,
            'showArchived' => $showArchived,
            'recurrenceOptions' => MonthlyRecurrence::options([
                MonthlyRecurrence::Monthly,
                MonthlyRecurrence::Quarterly,
                MonthlyRecurrence::Yearly,
            ]),
        ]);
    }

    public function store(StoreSavingsItemRequest $request, CreateSavingsItem $createSavingsItem): RedirectResponse
    {
        $createSavingsItem($request->user(), $request->validated());

        return back();
    }

    public function update(UpdateSavingsItemRequest $request, SavingsItem $saving, UpdateSavingsItem $updateSavingsItem): RedirectResponse
    {
        $updateSavingsItem($saving, $request->validated());

        return back();
    }

    public function destroy(SavingsItem $saving): RedirectResponse
    {
        Gate::authorize('delete', $saving);

        $saving->delete();

        return back();
    }

    public function updateStatus(Request $request, SavingsItem $saving, MarkSavingsItemStatus $markSavingsItemStatus): RedirectResponse
    {
        Gate::authorize('update', $saving);

        $status = SavingsItemStatus::from($request->string('status')->toString());

        $markSavingsItemStatus($saving, $status);

        return back();
    }

    public function recordContribution(SavingsItem $saving, RecordSavingsContribution $recordSavingsContribution): RedirectResponse
    {
        Gate::authorize('update', $saving);

        if ($saving->status === SavingsItemStatus::COMPLETED) {
            return back()->withErrors([
                'contribution' => 'This goal has already been completed.',
            ]);
        }

        if ($saving->hasContributedCurrentPeriod()) {
            return back()->withErrors([
                'contribution' => 'A contribution has already been recorded for this period.',
            ]);
        }

        $recordSavingsContribution($saving);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapItem(SavingsItem $item): array
    {
        return [
            'id' => $item->id,
            'type' => $item->type->value,
            'typeLabel' => $item->type->getReadable(),
            'name' => $item->name,
            'targetAmount' => (float) $item->target_amount,
            'installmentAmount' => (float) $item->installment_amount,
            'installmentsMade' => $item->installments_made,
            'targetProfit' => $item->target_profit !== null ? (float) $item->target_profit : null,
            'maturityDate' => $item->maturity_date?->toDateString(),
            'amountSaved' => $item->amountSaved(),
            'remainingToTarget' => $item->remainingToTarget(),
            'profitEarned' => $item->profitEarned(),
            'currencyCode' => $item->currency_code,
            'status' => $item->status->value,
            'statusLabel' => $item->status->getReadable(),
            'lastContributionDate' => $item->last_contribution_date?->toDateString(),
            'schedule' => $item->schedule?->toFrontendArray(),
            'nextContributionDate' => $item->nextPaymentDate()?->toDateString(),
            'canRecordContribution' => $item->status !== SavingsItemStatus::COMPLETED && ! $item->hasContributedCurrentPeriod(),
            'notes' => $item->notes,
        ];
    }
}
